<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Region;
use App\Services\DealerDirectoryService;
use App\Support\AppApi\PublicUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerController extends Controller
{
    public function __construct(
        private DealerDirectoryService $dealers,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 50);
        $page = $this->dealers->search($request, $perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn (Company $company) => $this->card($company))->values()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function map(Request $request): JsonResponse
    {
        return response()->json([
            'center' => $this->dealers->mapCenter($request),
            'markers' => $this->dealers->mapMarkers($request)->map(fn (array $marker) => [
                'id' => $marker['id'],
                'lat' => $marker['lat'],
                'lng' => $marker['lng'],
                'name' => $marker['name'],
                'city' => $marker['city'],
                'listings' => $marker['listings'],
                'verified' => $marker['verified'],
            ])->values()->all(),
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $region = Region::query()->findOrFail($request->integer('region_id'));

        return response()->json([
            'cities' => $this->dealers->citiesWithCounts($region),
        ]);
    }

    /** @return array<string, mixed> */
    private function card(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'logo_url' => PublicUrl::storage($company->logoUrl()),
            'location' => $company->locationLabel(),
            'city' => $company->city,
            'listings_count' => (int) $company->published_listings_count,
            'is_verified' => $company->isVerifiedDealer(),
        ];
    }
}
