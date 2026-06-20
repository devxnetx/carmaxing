<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportDatabaseToMysql extends Command
{
    protected $signature = 'db:export-mysql
                            {--from=sqlite : Source database connection name}
                            {--path= : Output .sql file path}
                            {--skip=migrations : Comma-separated table names to skip}';

    protected $description = 'Export SQLite data as MySQL-compatible INSERT statements (use after migrate on target)';

    public function handle(): int
    {
        $from = (string) $this->option('from');

        if (! config("database.connections.{$from}")) {
            $this->error("Unknown source connection [{$from}].");

            return self::FAILURE;
        }

        try {
            DB::connection($from)->getPdo();
        } catch (\Throwable $exception) {
            $this->error("Cannot connect to source [{$from}]: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $path = (string) ($this->option('path') ?: database_path('dumps/carmaxing-data.sql'));
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->error("Cannot create directory [{$directory}].");

            return self::FAILURE;
        }

        $skip = collect(explode(',', (string) $this->option('skip')))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->all();

        $tables = $this->tableNames($from, $skip);

        $handle = fopen($path, 'w');

        if ($handle === false) {
            $this->error("Cannot write to [{$path}].");

            return self::FAILURE;
        }

        fwrite($handle, "-- Carmaxing data export from [{$from}]\n");
        fwrite($handle, '-- Generated: '.now()->toIso8601String()."\n");
        fwrite($handle, "-- Run migrations on MySQL first, then import this file.\n\n");
        fwrite($handle, "SET NAMES utf8mb4;\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $totalRows = 0;

        foreach ($tables as $table) {
            $count = DB::connection($from)->table($table)->count();

            if ($count === 0) {
                continue;
            }

            $order = Schema::connection($from)->hasColumn($table, 'id') ? 'id' : Schema::connection($from)->getColumnListing($table)[0];
            $rows = DB::connection($from)->table($table)->orderBy($order)->get();
            $columns = Schema::connection($from)->getColumnListing($table);

            fwrite($handle, "-- Table: {$table} ({$count} rows)\n");

            foreach ($rows as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $values[] = $this->quoteValue($row->{$column} ?? null);
                }

                fwrite(
                    $handle,
                    'INSERT INTO `'.$table.'` (`'.implode('`, `', $columns).'`) VALUES ('.implode(', ', $values).");\n",
                );
            }

            fwrite($handle, "\n");
            $totalRows += $count;
            $this->line("  {$table}: {$count} rows");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        $this->newLine();
        $this->info("Exported {$totalRows} rows to {$path}");

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function tableNames(string $connection, array $skip): array
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

        $names = array_values(array_filter(
            $names,
            fn (string $table) => ! in_array($table, $skip, true),
        ));

        sort($names);

        return $names;
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $string = (string) $value;

        return "'".str_replace(['\\', "'", "\0", "\n", "\r"], ['\\\\', "''", '\\0', '\\n', '\\r'], $string)."'";
    }
}