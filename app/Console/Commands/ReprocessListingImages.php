<?php

namespace App\Console\Commands;

use App\Models\ListingImage;
use App\Services\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReprocessListingImages extends Command
{
    protected $signature = 'listings:reprocess-images {--force : Reprocess even if variants already exist}';

    protected $description = 'Generate WebP thumb/medium/large variants for existing listing photos';

    public function handle(ImageProcessor $processor): int
    {
        $variants = config('images.listing');
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        ListingImage::query()
            ->orderBy('id')
            ->chunkById(50, function ($images) use ($processor, $variants, &$processed, &$skipped, &$failed) {
                foreach ($images as $image) {
                    if (str_starts_with($image->path, 'http://') || str_starts_with($image->path, 'https://')) {
                        $skipped++;

                        continue;
                    }

                    if (! $this->option('force') && $image->path_thumb && $image->path_medium) {
                        $skipped++;

                        continue;
                    }

                    $disk = Storage::disk('public');

                    if (! $disk->exists($image->path)) {
                        $failed++;
                        $this->warn("Missing file: {$image->path}");

                        continue;
                    }

                    $temp = tempnam(sys_get_temp_dir(), 'img');
                    file_put_contents($temp, $disk->get($image->path));

                    try {
                        $oldPaths = [$image->path, $image->path_medium, $image->path_thumb];
                        $directory = "listings/{$image->listing_id}";
                        $result = $processor->processPath($temp, $directory, $variants);

                        $image->update([
                            'path' => $result['path'],
                            'path_medium' => $result['path_medium'],
                            'path_thumb' => $result['path_thumb'],
                            'width' => $result['width'],
                            'height' => $result['height'],
                        ]);

                        $processor->deletePaths(...array_values(array_unique(array_filter($oldPaths))));

                        $processed++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->warn("Image #{$image->id}: {$e->getMessage()}");
                    } finally {
                        @unlink($temp);
                    }
                }
            });

        $this->info("Processed {$processed}, skipped {$skipped}, failed {$failed}.");

        return self::SUCCESS;
    }
}