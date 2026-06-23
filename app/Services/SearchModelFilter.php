<?php

namespace App\Services;

use App\Models\VehicleModel;
use Illuminate\Http\Request;

class SearchModelFilter
{
    /** @return list<int> */
    public static function resolveIds(Request $request): array
    {
        $ids = [];

        if ($request->filled('model_ids')) {
            $ids = array_map('intval', (array) $request->input('model_ids'));
        } elseif ($request->filled('series_ids')) {
            foreach ((array) $request->input('series_ids') as $seriesId) {
                $series = VehicleModel::query()->find($seriesId);

                if ($series) {
                    $ids = array_merge($ids, $series->descendantIds());
                }
            }
        } elseif ($request->filled('model_id')) {
            $ids = [$request->integer('model_id')];
        }

        return array_values(array_unique(array_filter($ids)));
    }
}