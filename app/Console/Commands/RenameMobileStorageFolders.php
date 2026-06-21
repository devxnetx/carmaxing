<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RenameMobileStorageFolders extends Command
{
    protected $signature = 'storage:rename-mobile-folders';

    protected $description = 'Rename public storage folders from mobile-bg-* to carmaxing-*';

    /** @var array<string, string> */
    private array $replacements = [
        'mobile-bg-logo' => 'carmaxing-logo',
        'mobile-bg-cover' => 'carmaxing-cover',
    ];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $moved = 0;
        $updated = 0;

        foreach ($disk->allFiles('companies') as $path) {
            if (! $this->containsMobileFolder($path)) {
                continue;
            }

            $newPath = $this->renamePath($path);

            if ($newPath === $path || $disk->exists($newPath)) {
                continue;
            }

            $disk->move($path, $newPath);
            $moved++;
            $this->line("Moved {$path} -> {$newPath}");
        }

        Company::query()
            ->where(function ($query) {
                $query->where('logo', 'like', '%mobile-bg-%')
                    ->orWhere('cover_image', 'like', '%mobile-bg-%');
            })
            ->each(function (Company $company) use (&$updated) {
                $updates = [];

                foreach (['logo', 'cover_image'] as $field) {
                    $path = $company->{$field};

                    if (! is_string($path) || ! $this->containsMobileFolder($path)) {
                        continue;
                    }

                    $updates[$field] = $this->renamePath($path);
                }

                if ($updates === []) {
                    return;
                }

                $company->update($updates);
                $updated++;
            });

        $this->info("Moved {$moved} file(s), updated {$updated} company record(s).");

        return self::SUCCESS;
    }

    private function containsMobileFolder(string $path): bool
    {
        foreach (array_keys($this->replacements) as $folder) {
            if (str_contains($path, $folder)) {
                return true;
            }
        }

        return false;
    }

    private function renamePath(string $path): string
    {
        return str_replace(array_keys($this->replacements), array_values($this->replacements), $path);
    }
}