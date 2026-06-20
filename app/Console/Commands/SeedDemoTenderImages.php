<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Tender;
use App\Models\TenderImage;
use App\Services\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeedDemoTenderImages extends Command
{
    protected $signature = 'tenders:seed-images
                            {tender? : Tender slug or ID}
                            {--from-listing= : Copy processed images from a listing ID}
                            {--count=8 : Number of images to attach}';

    protected $description = 'Attach gallery images to a tender (copy from listing or download demo photos)';

    public function handle(ImageProcessor $imageProcessor): int
    {
        $tender = $this->resolveTender();

        if (! $tender) {
            $this->error('Tender not found.');

            return self::FAILURE;
        }

        $this->clearImages($tender);

        $fromListingId = $this->option('from-listing');

        if ($fromListingId) {
            $attached = $this->copyFromListing($tender, (int) $fromListingId);
        } else {
            $attached = $this->downloadDemoImages($tender, $imageProcessor, (int) $this->option('count'));
        }

        if ($attached < 1) {
            $this->error('No images were attached.');

            return self::FAILURE;
        }

        $this->info("Attached {$attached} image(s) to tender {$tender->reference_number} ({$tender->slug}).");

        return self::SUCCESS;
    }

    private function resolveTender(): ?Tender
    {
        $key = $this->argument('tender');

        if ($key) {
            return Tender::query()
                ->when(is_numeric($key), fn ($q) => $q->where('id', (int) $key))
                ->when(! is_numeric($key), fn ($q) => $q->where('slug', $key))
                ->first();
        }

        return Tender::query()->orderBy('id')->first();
    }

    private function clearImages(Tender $tender): void
    {
        $tender->images()->get()->each(function (TenderImage $image) {
            $image->deleteFiles();
            $image->delete();
        });
    }

    private function copyFromListing(Tender $tender, int $listingId): int
    {
        $listing = Listing::query()->with('images')->find($listingId);

        if (! $listing) {
            $this->error("Listing {$listingId} not found.");

            return 0;
        }

        $localImages = $listing->images
            ->filter(fn (ListingImage $image) => ! $image->isRemote())
            ->take((int) $this->option('count'));

        if ($localImages->isEmpty()) {
            $this->warn('Listing has no local images to copy.');

            return 0;
        }

        $disk = Storage::disk('public');
        $attached = 0;

        foreach ($localImages->values() as $index => $source) {
            $basename = Str::uuid()->toString();
            $directory = "tenders/{$tender->id}";
            $disk->makeDirectory($directory);

            $paths = [
                'path' => $this->copyVariant($disk, $source->path, $directory, $basename, 'large'),
                'path_medium' => $this->copyVariant($disk, $source->path_medium, $directory, $basename, 'medium'),
                'path_thumb' => $this->copyVariant($disk, $source->path_thumb, $directory, $basename, 'thumb'),
            ];

            if (! $paths['path']) {
                continue;
            }

            $tender->images()->create([
                ...$paths,
                'width' => $source->width,
                'height' => $source->height,
                'sort_order' => $index + 1,
                'is_primary' => $index === 0,
            ]);

            $attached++;
        }

        return $attached;
    }

    private function copyVariant($disk, ?string $sourcePath, string $directory, string $basename, string $variant): ?string
    {
        if (! $sourcePath || ! $disk->exists($sourcePath)) {
            return null;
        }

        $suffix = match ($variant) {
            'medium' => '_medium',
            'thumb' => '_thumb',
            default => '_large',
        };

        $target = "{$directory}/{$basename}{$suffix}.webp";

        if (! $disk->exists($target)) {
            $disk->copy($sourcePath, $target);
        }

        return $target;
    }

    private function downloadDemoImages(Tender $tender, ImageProcessor $imageProcessor, int $count): int
    {
        $attached = 0;
        $seed = Str::slug($tender->vehicleName() ?: 'demo-tender');

        for ($index = 1; $index <= $count; $index++) {
            $url = "https://picsum.photos/seed/{$seed}-{$index}/1600/1200";

            try {
                $response = Http::timeout(30)->get($url);

                if (! $response->successful()) {
                    $this->warn("Skipped image {$index}: download failed.");
                    continue;
                }

                $processed = $imageProcessor->processBinary(
                    $response->body(),
                    "tenders/{$tender->id}",
                    config('images.listing'),
                    $response->header('Content-Type'),
                );
            } catch (\Throwable $exception) {
                $this->warn("Skipped image {$index}: {$exception->getMessage()}");
                continue;
            }

            $tender->images()->create([
                ...$processed,
                'sort_order' => $index,
                'is_primary' => $index === 1,
            ]);

            $attached++;
        }

        return $attached;
    }
}