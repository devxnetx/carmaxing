<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Listing;
use App\Models\VehicleBrand;
use App\Support\AppApi\ListingPayload;
use App\Support\AppApi\PublicUrl;
use App\Support\HtmlToPlainText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(ctype_digit($id), 404);

        $company = Company::query()->with('region')->findOrFail($id);

        $brandIds = Listing::query()
            ->published()
            ->where('company_id', $company->id)
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id');

        $brands = VehicleBrand::query()
            ->whereIn('id', $brandIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $listingsQuery = Listing::query()
            ->published()
            ->where('company_id', $company->id)
            ->with(['brand', 'model', 'images', 'region'])
            ->when($request->filled('ad_number'), fn ($query) => $query->where('ad_number', $request->integer('ad_number')))
            ->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->integer('brand_id')));

        match ($request->input('sort', 'newest')) {
            'price_asc' => $listingsQuery->orderBy('price'),
            'price_desc' => $listingsQuery->orderByDesc('price'),
            'year_desc' => $listingsQuery->orderByDesc('year'),
            'mileage_asc' => $listingsQuery->orderBy('mileage'),
            default => $listingsQuery->orderByDesc('published_at'),
        };

        $perPage = min(max($request->integer('per_page', 12), 1), 50);
        $listings = $listingsQuery->paginate($perPage)->withQueryString();
        $ids = $listings->getCollection()->pluck('id')->all();

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'description' => HtmlToPlainText::convert($company->description),
                'logo_url' => PublicUrl::storage($company->logoUrl()),
                'cover_url' => PublicUrl::storage($company->coverUrl()),
                'phone' => $company->phone,
                'email' => $company->email,
                'website' => $company->website,
                'address' => $company->address,
                'city' => $company->city,
                'region' => $company->region ? [
                    'id' => $company->region->id,
                    'name' => $company->region->name,
                ] : null,
                'member_since_year' => $company->member_since_year,
                'is_verified' => $company->isVerifiedDealer(),
                'listings_count' => $listings->total(),
                'web_url' => PublicUrl::page(route('company.show', $company)),
            ],
            'brands' => $brands->map(fn (VehicleBrand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
            ])->values()->all(),
            'listings' => ListingPayload::page($listings, $this->favoritedIds($ids)),
        ]);
    }

    /** @param  list<int>  $ids */
    private function favoritedIds(array $ids): array
    {
        $user = auth('sanctum')->user();

        if (! $user || $ids === []) {
            return [];
        }

        return $user->favorites()
            ->whereIn('listing_id', $ids)
            ->pluck('listing_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
