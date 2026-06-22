<?php

namespace App\Http\Controllers;

use App\Jobs\ImportMobileBgListings;
use App\Models\MobileBgImportRun;
use App\Services\MobileBg\MobileBgClient;
use App\Support\ManagedQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MobileBgImportController extends Controller
{
    public function store(Request $request, MobileBgClient $client): RedirectResponse|JsonResponse
    {
        $company = $request->user()->company;
        abort_unless($company, 403);

        $data = $request->validate([
            'mobile_bg_url' => ['required', 'string', 'max:255'],
            'sync_images' => ['boolean'],
        ]);

        $sourceUrl = $client->normalizeDealerUrl($data['mobile_bg_url']);

        $activeRun = MobileBgImportRun::query()
            ->where('company_id', $company->id)
            ->whereIn('status', [MobileBgImportRun::STATUS_PENDING, MobileBgImportRun::STATUS_RUNNING])
            ->exists();

        if ($activeRun) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.mobile_bg_import_running')], 409);
            }

            return back()->with('error', __('messages.mobile_bg_import_running'));
        }

        $run = MobileBgImportRun::query()->create([
            'company_id' => $company->id,
            'source_url' => $sourceUrl,
            'status' => MobileBgImportRun::STATUS_PENDING,
        ]);

        $company->update(['mobile_bg_url' => $sourceUrl]);

        $this->dispatchImport($run, $request->boolean('sync_images', true));

        $run->refresh();
        $message = config('queue.default') === 'sync' || $run->isFinished()
            ? __('messages.mobile_bg_import_started_sync')
            : __('messages.mobile_bg_import_started');

        if ($request->expectsJson()) {
            return response()->json([
                'run_id' => $run->id,
                'message' => $message,
                'finished' => $run->isFinished(),
            ]);
        }

        return back()->with('success', $message);
    }

    public function status(Request $request, MobileBgImportRun $run): JsonResponse
    {
        $company = $request->user()->company;
        abort_unless($company && $run->company_id === $company->id, 403);

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'total_found' => $run->total_found,
            'created_count' => $run->created_count,
            'updated_count' => $run->updated_count,
            'failed_count' => $run->failed_count,
            'errors' => $run->errors,
            'started_at' => $run->started_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
            'finished' => $run->isFinished(),
        ]);
    }

    private function dispatchImport(MobileBgImportRun $run, bool $syncImages): void
    {
        ManagedQueue::dispatch(new ImportMobileBgListings($run, $syncImages));
    }
}