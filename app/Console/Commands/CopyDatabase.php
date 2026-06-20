<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CopyDatabase extends Command
{
    protected $signature = 'db:copy
                            {--from=sqlite : Source database connection name}
                            {--to=mysql : Target database connection name}
                            {--migrate : Run migrations on the target connection before copying}
                            {--truncate : Empty target tables before inserting}
                            {--only= : Comma-separated table names to copy}
                            {--skip=migrations : Comma-separated table names to skip}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Copy table data from one database connection to another (e.g. SQLite → MySQL)';

    public function handle(): int
    {
        $from = (string) $this->option('from');
        $to = (string) $this->option('to');

        if (! config("database.connections.{$from}")) {
            $this->error("Unknown source connection [{$from}].");

            return self::FAILURE;
        }

        if (! config("database.connections.{$to}")) {
            $this->error("Unknown target connection [{$to}].");

            return self::FAILURE;
        }

        if ($from === $to) {
            $this->error('Source and target connections must be different.');

            return self::FAILURE;
        }

        try {
            DB::connection($from)->getPdo();
        } catch (\Throwable $exception) {
            $this->error("Cannot connect to source [{$from}]: {$exception->getMessage()}");

            return self::FAILURE;
        }

        try {
            DB::connection($to)->getPdo();
        } catch (\Throwable $exception) {
            $this->error("Cannot connect to target [{$to}]: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if ($this->option('migrate')) {
            $this->info("Running migrations on [{$to}]…");
            $this->call('migrate', [
                '--database' => $to,
                '--force' => true,
            ]);
        }

        $tables = $this->resolveTables($from);

        if ($tables === []) {
            $this->warn('No tables matched the copy criteria.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Copy ".count($tables)." tables from [{$from}] to [{$to}]?", true)) {
            return self::SUCCESS;
        }

        Schema::connection($to)->disableForeignKeyConstraints();

        if ($this->option('truncate')) {
            $this->info('Truncating target tables…');

            foreach (array_reverse($tables) as $table) {
                if (! Schema::connection($to)->hasTable($table)) {
                    continue;
                }

                DB::connection($to)->table($table)->truncate();
            }
        }

        $copied = 0;

        foreach ($tables as $table) {
            if (! Schema::connection($to)->hasTable($table)) {
                $this->warn("Skipping {$table}: missing on target (run with --migrate first).");

                continue;
            }

            $query = DB::connection($from)->table($table);
            $count = (clone $query)->count();

            if ($count === 0) {
                $this->line("  {$table}: 0 rows");

                continue;
            }

            $query->orderBy($this->orderColumn($from, $table))->chunk(200, function (Collection $chunk) use ($table, $to) {
                DB::connection($to)->table($table)->insert(
                    $chunk->map(fn ($row) => (array) $row)->all(),
                );
            });

            $copied += $count;
            $this->line("  {$table}: {$count} rows");
        }

        if (config("database.connections.{$to}.driver") === 'mysql') {
            foreach ($tables as $table) {
                if (! Schema::connection($to)->hasTable($table) || ! Schema::connection($to)->hasColumn($table, 'id')) {
                    continue;
                }

                $maxId = DB::connection($to)->table($table)->max('id');

                if ($maxId) {
                    DB::connection($to)->statement(
                        "ALTER TABLE `{$table}` AUTO_INCREMENT = ".((int) $maxId + 1),
                    );
                }
            }
        }

        Schema::connection($to)->enableForeignKeyConstraints();

        $this->newLine();
        $this->info("Copied {$copied} rows from [{$from}] to [{$to}].");

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function resolveTables(string $connection): array
    {
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            $tables = DB::connection($connection)->select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'",
            );
            $names = collect($tables)->pluck('name')->map(fn ($name) => (string) $name)->all();
        } else {
            $names = Schema::connection($connection)->getTableListing();
        }

        $skip = collect(explode(',', (string) $this->option('skip')))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->all();

        $names = array_values(array_filter(
            $names,
            fn (string $table) => ! in_array($table, $skip, true),
        ));

        if ($only = trim((string) $this->option('only'))) {
            $onlyTables = collect(explode(',', $only))
                ->map(fn ($name) => trim($name))
                ->filter()
                ->all();

            $names = array_values(array_intersect($names, $onlyTables));
        }

        sort($names);

        return $names;
    }

    private function orderColumn(string $connection, string $table): string
    {
        if (Schema::connection($connection)->hasColumn($table, 'id')) {
            return 'id';
        }

        return Schema::connection($connection)->getColumnListing($table)[0] ?? 'rowid';
    }
}