<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\CompareService;
use App\Support\ComparePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function __construct(
        private CompareService $compare,
    ) {}

    public function index(Request $request): View
    {
        $listings = $this->compare->listings($request);

        return view('compare.index', [
            'listings' => $listings,
            'specSections' => ComparePresenter::specSections(),
            'featureSections' => ComparePresenter::featureSections($listings),
        ]);
    }

    public function add(Request $request, Listing $listing): JsonResponse
    {
        $count = $this->compare->add($request, $listing->id);

        return response()->json([
            'count' => $count,
            'ids' => $this->compare->ids($request),
            'message' => __('messages.compare_added'),
        ]);
    }

    public function remove(Request $request, Listing $listing): JsonResponse
    {
        $count = $this->compare->remove($request, $listing->id);

        return response()->json([
            'count' => $count,
            'ids' => $this->compare->ids($request),
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $this->compare->clear($request);

        return response()->json(['count' => 0, 'ids' => []]);
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json([
            'count' => count($this->compare->ids($request)),
            'ids' => $this->compare->ids($request),
        ]);
    }
}