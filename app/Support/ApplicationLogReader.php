<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use SplFileObject;

final class ApplicationLogReader
{
    private const int MAX_TAIL_LINES = 2000;

    public function directory(): string
    {
        return storage_path('logs');
    }

    /**
     * @return Collection<int, array{filename: string, path: string, size: int, modified_at: int}>
     */
    public function files(): Collection
    {
        $directory = $this->directory();

        if (! is_dir($directory)) {
            return collect();
        }

        return collect(scandir($directory) ?: [])
            ->filter(fn (string $name) => $this->isLogFilename($name))
            ->map(function (string $filename) use ($directory) {
                $path = $directory.DIRECTORY_SEPARATOR.$filename;

                return [
                    'filename' => $filename,
                    'path' => $path,
                    'size' => (int) filesize($path),
                    'modified_at' => (int) filemtime($path),
                ];
            })
            ->sortByDesc('modified_at')
            ->values();
    }

    public function resolve(string $filename): string
    {
        if (! $this->isLogFilename($filename)) {
            throw new RuntimeException('Invalid log file name.');
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            throw new RuntimeException('Log file not found.');
        }

        return $path;
    }

    public function tail(string $filename, int $lines = 500, bool $errorsOnly = false): string
    {
        $lines = max(50, min(self::MAX_TAIL_LINES, $lines));
        $path = $this->resolve($filename);
        $content = $this->readLastLines($path, $lines);

        if (! $errorsOnly) {
            return $content;
        }

        return $this->filterErrorLines($content);
    }

    public function isLogFilename(string $filename): bool
    {
        return $filename !== '.'
            && $filename !== '..'
            && ! str_contains($filename, '/')
            && ! str_contains($filename, '\\')
            && str_ends_with($filename, '.log');
    }

    private function readLastLines(string $path, int $lines): string
    {
        $file = new SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $start = max(0, $lastLine - $lines);
        $buffer = [];

        for ($line = $start; $line <= $lastLine; $line++) {
            $file->seek($line);
            $buffer[] = rtrim((string) $file->current(), "\r\n");
        }

        return implode("\n", $buffer);
    }

    private function filterErrorLines(string $content): string
    {
        $pattern = '/\.(ERROR|CRITICAL|ALERT|EMERGENCY):|Exception|ErrorException|Fatal error|Stack trace:/i';

        $chunks = preg_split("/\n(?=\[\d{4}-\d{2}-\d{2})/", $content) ?: [];
        $matches = [];

        foreach ($chunks as $chunk) {
            if (preg_match($pattern, $chunk)) {
                $matches[] = trim($chunk);
            }
        }

        if ($matches === []) {
            return __('admin.logs_no_errors_in_tail');
        }

        return implode("\n\n".str_repeat('─', 72)."\n\n", $matches);
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        return Str::of((string) round($bytes / 1024, 1))->append(' KB');
    }
}