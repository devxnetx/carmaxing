<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BidCarsImportRun;
use App\Services\BidCars\BidCarsLotImporter;
use App\Support\BidCarsImportConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BidCarsImportApiController extends Controller
{
    public function store(Request $request, BidCarsLotImporter $lotImporter): JsonResponse
    {
        $request->validate([
            'brands' => ['nullable', 'array'],
            'brands.*' => ['required', 'string', 'max:100'],
            'pages_per_brand' => ['nullable'],
            'pages' => ['required', 'array', 'min:1'],
            'pages.*.brand' => ['required', 'string', 'max:100'],
            'pages.*.current_page' => ['required', 'integer', 'min:1'],
            'pages.*.items' => ['required', 'array'],
            'pages.*.items.*' => ['required', 'array'],
            'pages.*.items.*.lot' => ['required', 'string', 'max:32'],
        ]);

        $pages = $request->input('pages', []);
        $brands = $request->input('brands') ?? collect($pages)->pluck('brand')->unique()->values()->all();
        $pagesPerBrand = BidCarsImportConfig::normalizePagesPerBrand($request->input('pages_per_brand') ?? 1);
        $pagesPerBrandStored = BidCarsImportConfig::pagesPerBrandForStorage($pagesPerBrand);

        $run = BidCarsImportRun::query()->create([
            'status' => BidCarsImportRun::STATUS_RUNNING,
            'filters' => BidCarsImportConfig::filters(),
            'pages_per_brand' => $pagesPerBrandStored,
            'started_at' => now(),
        ]);

        $pageSummaries = [];
        $errors = [];

        try {
            foreach ($pages as $pagePayload) {
                $brand = $pagePayload['brand'];
                $currentPage = (int) $pagePayload['current_page'];
                $items = $pagePayload['items'];

                if ($items === []) {
                    $errors[] = [
                        'brand' => $brand,
                        'page' => $currentPage,
                        'message' => 'No lot data returned.',
                    ];

                    continue;
                }

                $firstLot = $items[0]['lot'] ?? null;
                $pageSummaries[] = [
                    'brand' => $brand,
                    'page' => $currentPage,
                    'count' => count($items),
                    'first_lot' => $firstLot,
                ];

                foreach ($items as $item) {
                    $lot = $lotImporter->upsert($item, $run);

                    $run->increment('total_fetched');

                    if ($lot->wasRecentlyCreated) {
                        $run->increment('created_count');
                    } else {
                        $run->increment('updated_count');
                    }
                }
            }

            $run->update([
                'status' => BidCarsImportRun::STATUS_COMPLETED,
                'errors' => $errors === [] ? null : $errors,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $errors[] = ['message' => $e->getMessage()];
            $run->update([
                'status' => BidCarsImportRun::STATUS_FAILED,
                'failed_count' => max(1, (int) $run->failed_count),
                'errors' => $errors,
                'completed_at' => now(),
            ]);

            throw $e;
        }

        return response()->json([
            'import_run_id' => $run->id,
            'status' => $run->status,
            'brands' => $brands,
            'pages_per_brand' => BidCarsImportConfig::isFullPages($pagesPerBrand) ? 'full' : $pagesPerBrand,
            'total_fetched' => $run->total_fetched,
            'created_count' => $run->created_count,
            'updated_count' => $run->updated_count,
            'page_summaries' => $pageSummaries,
            'errors' => $errors === [] ? null : $errors,
        ]);
    }
}