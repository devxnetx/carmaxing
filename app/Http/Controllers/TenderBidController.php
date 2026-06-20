<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\TenderBid;
use App\Services\Tenders\TenderBidService;
use App\Services\Tenders\TenderStatePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderBidController extends Controller
{
    public function __construct(
        private TenderBidService $bidService,
        private TenderStatePresenter $statePresenter,
    ) {}

    public function store(Request $request, Tender $tender): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $this->bidService->place($tender, $request->user(), (int) $data['amount']);

        return response()->json([
            'message' => __('tenders.bid_placed'),
            'state' => $this->statePresenter->forPoll($tender->fresh(), $request->user()),
        ]);
    }

    public function destroy(Request $request, Tender $tender, TenderBid $bid): JsonResponse
    {
        abort_unless((int) $bid->tender_id === (int) $tender->id, 404);

        $this->bidService->revoke($bid, $request->user());

        return response()->json([
            'message' => __('tenders.bid_revoked'),
            'state' => $this->statePresenter->forPoll($tender->fresh(), $request->user()),
        ]);
    }
}