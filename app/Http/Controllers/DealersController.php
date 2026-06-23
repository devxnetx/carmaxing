<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Services\DealerDirectoryService;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealersController extends Controller
{
    public function __construct(
        private DealerDirectoryService $dealers,
    ) {}

    public function index(Request $request): View
    {
        $companies = $this->dealers->search($request);

        return view('dealers.index', [
            'companies' => $companies,
            'regions' => CatalogCache::regions(),
            'filters' => [
                'region_id' => $request->input('region_id'),
                'city' => $request->input('city'),
            ],
            'mapCenter' => $this->dealers->mapCenter($request),
            'mapMarkers' => $this->dealers->mapMarkers($request),
        ]);
    }

    public function cities(Region $region): JsonResponse
    {
        return response()->json([
            'cities' => $this->dealers->citiesWithCounts($region),
        ]);
    }
}