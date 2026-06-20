<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageProcessor
{
    /** @param  array<string, array{max: int, quality: int}>  $variants */
    public function processUpload(UploadedFile $file, string $directory, array $variants): array
    {
        $source = $this->loadImage($file->getRealPath(), $file->getMimeType() ?: $file->getClientMimeType());

        return $this->encodeVariants($source, $directory, $variants);
    }

    /** @param  array<string, array{max: int, quality: int}>  $variants */
    public function processBinary(string $binary, string $directory, array $variants, ?string $hintMime = null): array
    {
        $temp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($temp, $binary);

        try {
            $source = $this->loadImage($temp, $hintMime ?: $this->guessMime($binary));
        } finally {
            @unlink($temp);
        }

        return $this->encodeVariants($source, $directory, $variants);
    }

    /** @param  array<string, array{max: int, quality: int}>  $variants */
    public function processPath(string $absolutePath, string $directory, array $variants): array
    {
        $source = $this->loadImage($absolutePath, mime_content_type($absolutePath) ?: null);

        return $this->encodeVariants($source, $directory, $variants);
    }

    /** @param  array{max: int, quality: int}  $config */
    public function processSingleBinary(string $binary, string $directory, array $config, ?string $hintMime = null): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($temp, $binary);

        try {
            $source = $this->loadImage($temp, $hintMime ?: $this->guessMime($binary));
        } finally {
            @unlink($temp);
        }

        return $this->encodeSingle($source, $directory, $config);
    }

    public function processSingleUpload(UploadedFile $file, string $directory, array $config): string
    {
        $source = $this->loadImage($file->getRealPath(), $file->getMimeType() ?: $file->getClientMimeType());

        return $this->encodeSingle($source, $directory, $config);
    }

    /** @param  array{max: int, quality: int}  $config */
    private function encodeSingle(\GdImage $source, string $directory, array $config): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $resized = $this->resizeToMax($source, $width, $height, $config['max']);
        imagedestroy($source);

        $disk = Storage::disk('public');
        $disk->makeDirectory($directory);
        $relative = "{$directory}/".Str::uuid()->toString().'.webp';
        $absolute = $disk->path($relative);

        if (! imagewebp($resized, $absolute, $config['quality'])) {
            imagedestroy($resized);
            throw new RuntimeException('Failed to encode image.');
        }

        imagedestroy($resized);

        return $relative;
    }

    public function deletePaths(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if ($path && ! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /** @return resource|\GdImage */
    private function loadImage(string $path, ?string $mime): \GdImage
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required for image processing.');
        }

        $image = match (true) {
            str_contains((string) $mime, 'png') => @imagecreatefrompng($path),
            str_contains((string) $mime, 'webp') => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            str_contains((string) $mime, 'gif') => @imagecreatefromgif($path),
            default => @imagecreatefromjpeg($path),
        };

        if ($image === false) {
            $image = @imagecreatefromstring(file_get_contents($path));
        }

        if (! $image instanceof \GdImage) {
            throw new RuntimeException('Unsupported or corrupt image file.');
        }

        if (function_exists('exif_read_data') && str_contains((string) $mime, 'jpeg')) {
            $image = $this->applyExifOrientation($image, $path);
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    /** @param  resource|\GdImage  $source */
    private function applyExifOrientation(\GdImage $source, string $path): \GdImage
    {
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        return match ($orientation) {
            3 => imagerotate($source, 180, 0),
            6 => imagerotate($source, -90, 0),
            8 => imagerotate($source, 90, 0),
            default => $source,
        };
    }

    /**
     * @param  resource|\GdImage  $source
     * @param  array<string, array{max: int, quality: int}>  $variants
     * @return array{path: string, path_medium: string, path_thumb: string, width: int, height: int}
     */
    private function encodeVariants(\GdImage $source, string $directory, array $variants): array
    {
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        $basename = Str::uuid()->toString();
        $disk = Storage::disk('public');
        $disk->makeDirectory($directory);

        $paths = [];
        $largeWidth = $originalWidth;
        $largeHeight = $originalHeight;

        foreach ($variants as $name => $config) {
            $resized = $this->resizeToMax($source, $originalWidth, $originalHeight, $config['max']);
            $relative = "{$directory}/{$basename}_{$name}.webp";
            $absolute = $disk->path($relative);

            if (! imagewebp($resized, $absolute, $config['quality'])) {
                imagedestroy($resized);
                throw new RuntimeException("Failed to encode {$name} variant.");
            }

            if ($name === 'large') {
                $largeWidth = imagesx($resized);
                $largeHeight = imagesy($resized);
            }

            imagedestroy($resized);
            $paths[$name] = $relative;
        }

        imagedestroy($source);

        return [
            'path' => $paths['large'],
            'path_medium' => $paths['medium'],
            'path_thumb' => $paths['thumb'],
            'width' => $largeWidth,
            'height' => $largeHeight,
        ];
    }

    /** @return \GdImage */
    private function resizeToMax(\GdImage $source, int $width, int $height, int $max): \GdImage
    {
        if ($width <= $max && $height <= $max) {
            $canvas = imagecreatetruecolor($width, $height);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

            return $canvas;
        }

        $ratio = min($max / $width, $max / $height);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    private function guessMime(string $binary): ?string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($binary, "\x89PNG")) {
            return 'image/png';
        }

        if (str_starts_with($binary, 'RIFF') && str_contains(substr($binary, 0, 16), 'WEBP')) {
            return 'image/webp';
        }

        return null;
    }
}