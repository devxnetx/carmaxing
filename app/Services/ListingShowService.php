<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ListingShowService
{
    public function __construct(
        private MarketValueService $marketValue,
    ) {}

    /**
     * @return array{
     *     listing: Listing,
     *     similar: Collection,
     *     dealerListings: Collection,
     *     featureCategories: Collection,
     *     marketEstimate: ?array,
     *     latestPriceChange: ?\App\Models\ListingPriceChange
     * }
     */
    public function cachedShowData(Listing $listing): array
    {
        if ($listing->status === ListingStatus::Draft) {
            return $this->buildShowData($listing);
        }

        $locale = app()->getLocale();

        $data = Cache::remember(
            $this->cacheKey($listing->id, $locale),
            $this->ttlSeconds(),
            fn () => $this->buildShowData($listing),
        );

        $freshViews = Listing::query()->whereKey($listing->id)->value('views_count');
        $data['listing']->setAttribute('views_count', (int) $freshViews);

        return $data;
    }

    public function forget(Listing $listing): void
    {
        foreach (['bg', 'en'] as $locale) {
            Cache::forget($this->cacheKey($listing->id, $locale));
        }
    }

    private function cacheKey(int $listingId, string $locale): string
    {
        return "listing:show:{$listingId}:{$locale}";
    }

    private function ttlSeconds(): int
    {
        $hours = max(1, (int) config('listings.show_cache_ttl_hours', 1));

        return $hours * 3600;
    }

    /**
     * @return array{
     *     listing: Listing,
     *     similar: Collection,
     *     dealerListings: Collection,
     *     featureCategories: Collection,
     *     marketEstimate: ?array,
     *     latestPriceChange: ?\App\Models\ListingPriceChange
     * }
     */
    private function buildShowData(Listing $listing): array
    {
        $listing = Listing::query()
            ->whereKey($listing->id)
            ->with([
                'brand',
                'model.parent',
                'region',
                'images',
                'features.category',
                'user',
                'priceChanges',
                'company' => fn ($query) => $query
                    ->with('region')
                    ->withCount([
                        'listings as listings_count' => fn ($q) => $q->where('status', ListingStatus::Published),
                    ]),
            ])
            ->firstOrFail();

        $featureCategories = $listing->features
            ->groupBy(fn ($feature) => $feature->category_id)
            ->map(function ($features) {
                $category = $features->first()->category;

                return (object) [
                    'name' => $category->name,
                    'sort_order' => $category->sort_order,
                    'features' => $features->sortBy('sort_order')->values(),
                ];
            })
            ->sortBy('sort_order')
            ->values();

        $dealerListings = $listing->company_id
            ? Listing::query()
                ->published()
                ->where('company_id', $listing->company_id)
                ->where('id', '!=', $listing->id)
                ->with(['brand', 'model.parent', 'images', 'region', 'features', 'company'])
                ->latest('published_at')
                ->limit(4)
                ->get()
            : collect();

        $similar = Listing::query()
            ->published()
            ->where('brand_id', $listing->brand_id)
            ->where('id', '!=', $listing->id)
            ->with(['brand', 'model.parent', 'images', 'region', 'features', 'company'])
            ->limit(4)
            ->get();

        return [
            'listing' => $listing,
            'similar' => $similar,
            'dealerListings' => $dealerListings,
            'featureCategories' => $featureCategories,
            'marketEstimate' => $this->marketValue->estimate($listing),
            'latestPriceChange' => $listing->priceChanges->first(),
        ];
    }
}