<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Support\ApiDocsCatalog;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function api(): View
    {
        $user = auth()->user();
        $isCompanyUser = $user?->isCompany() ?? false;
        $hasActiveApiKey = $isCompanyUser && ($user->company?->apiKeys()->where('is_active', true)->exists() ?? false);
        $sampleListingId = null;

        if ($hasActiveApiKey && $user->company) {
            $listing = Listing::query()
                ->where('company_id', $user->company->id)
                ->latest('id')
                ->first();

            $sampleListingId = $listing ? (string) ($listing->ad_number ?? $listing->id) : null;
        }

        $baseUrl = config('api.base_url');
        $maxPerPage = config('api.max_per_page');

        return view('docs.api', [
            'baseUrl' => $baseUrl,
            'requestsPerMinute' => config('api.requests_per_minute'),
            'listingsPerDay' => config('api.listings_per_day'),
            'maxPerPage' => $maxPerPage,
            'isCompanyUser' => $isCompanyUser,
            'hasActiveApiKey' => $hasActiveApiKey,
            'canTryApi' => $hasActiveApiKey,
            'endpoints' => ApiDocsCatalog::endpoints($baseUrl, $maxPerPage, $sampleListingId ?? '1001'),
            'sessionApiKey' => session('new_api_key'),
        ]);
    }
}