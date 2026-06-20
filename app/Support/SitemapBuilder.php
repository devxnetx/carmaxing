<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Listing;
use App\Models\VehicleBrand;

class SitemapBuilder
{
    public function xmlUrls(): array
    {
        $urls = $this->staticPages();

        foreach (VehicleBrand::query()->where('is_popular', true)->orderBy('sort_order')->get() as $brand) {
            $urls[] = $this->entry(
                route('search', ['brand_id' => $brand->id]),
                priority: '0.8',
                changefreq: 'daily',
            );
        }

        Listing::query()
            ->published()
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->limit(5000)
            ->each(function (Listing $listing) use (&$urls) {
                $urls[] = $this->entry(
                    route('listings.show', $listing),
                    lastmod: $listing->updated_at->toAtomString(),
                    priority: '0.7',
                    changefreq: 'weekly',
                );
            });

        Company::query()
            ->select(['slug', 'updated_at'])
            ->orderBy('name')
            ->each(function (Company $company) use (&$urls) {
                $urls[] = $this->entry(
                    route('company.show', $company),
                    lastmod: $company->updated_at->toAtomString(),
                    priority: '0.6',
                    changefreq: 'weekly',
                );
            });

        return $urls;
    }

    public function htmlSections(): array
    {
        return [
            [
                'title' => __('messages.sitemap_section_pages'),
                'links' => collect($this->staticPages())->map(fn ($url) => [
                    'label' => $url['label'],
                    'url' => $url['loc'],
                ])->all(),
            ],
            [
                'title' => __('messages.sitemap_section_brands'),
                'links' => VehicleBrand::query()
                    ->where('is_popular', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (VehicleBrand $brand) => [
                        'label' => $brand->name,
                        'url' => route('search', ['brand_id' => $brand->id]),
                    ])
                    ->all(),
            ],
            [
                'title' => __('messages.sitemap_section_dealers'),
                'links' => Company::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Company $company) => [
                        'label' => $company->name,
                        'url' => route('company.show', $company),
                    ])
                    ->all(),
            ],
            [
                'title' => __('messages.sitemap_section_listings'),
                'links' => Listing::query()
                    ->published()
                    ->with(['brand', 'model'])
                    ->orderByDesc('published_at')
                    ->limit(100)
                    ->get()
                    ->map(fn (Listing $listing) => [
                        'label' => $listing->title,
                        'url' => route('listings.show', $listing),
                    ])
                    ->all(),
                'note' => __('messages.sitemap_listings_note'),
            ],
        ];
    }

    private function staticPages(): array
    {
        return [
            $this->entry(route('home'), label: __('messages.home'), priority: '1.0', changefreq: 'daily'),
            $this->entry(route('search'), label: __('messages.search'), priority: '0.9', changefreq: 'hourly'),
            $this->entry(route('pages.about'), label: __('messages.about_us'), priority: '0.5', changefreq: 'monthly'),
            $this->entry(route('pages.contact'), label: __('messages.contact_us'), priority: '0.5', changefreq: 'monthly'),
            $this->entry(route('sitemap.page'), label: __('messages.sitemap'), priority: '0.4', changefreq: 'weekly'),
            $this->entry(route('docs.api'), label: __('messages.api_docs'), priority: '0.4', changefreq: 'monthly'),
            $this->entry(route('legal.privacy'), label: __('messages.privacy_policy'), priority: '0.3', changefreq: 'yearly'),
            $this->entry(route('legal.terms'), label: __('messages.terms_of_service'), priority: '0.3', changefreq: 'yearly'),
            $this->entry(route('legal.cookies'), label: __('messages.cookie_policy'), priority: '0.3', changefreq: 'yearly'),
        ];
    }

    private function entry(
        string $loc,
        ?string $label = null,
        ?string $lastmod = null,
        string $priority = '0.5',
        string $changefreq = 'monthly',
    ): array {
        return array_filter([
            'loc' => $loc,
            'label' => $label,
            'lastmod' => $lastmod,
            'priority' => $priority,
            'changefreq' => $changefreq,
        ], fn ($value) => $value !== null);
    }
}