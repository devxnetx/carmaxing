<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Services\Tenders\TenderStatePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TenderController extends Controller
{
    public function __construct(
        private TenderStatePresenter $statePresenter,
    ) {}

    public function index(): View
    {
        $tenders = Tender::query()
            ->active()
            ->with(['brand', 'model.parent', 'images', 'region', 'bids'])
            ->orderBy('ends_at')
            ->paginate(12);

        return view('tenders.index', compact('tenders'));
    }

    public function show(Tender $tender): View
    {
        abort_unless(
            $tender->status !== \App\Enums\TenderStatus::Cancelled,
            404,
        );

        $tender->load(['brand', 'model.parent', 'images', 'region']);

        $state = $this->statePresenter->forPoll($tender, auth()->user());
        $isSeller = auth()->check() && $tender->isOwnedBy(auth()->user());

        return view('tenders.show', compact('tender', 'state', 'isSeller'));
    }

    public function state(Tender $tender): JsonResponse
    {
        abort_unless(
            $tender->status !== \App\Enums\TenderStatus::Cancelled,
            404,
        );

        return response()->json(
            $this->statePresenter->forPoll($tender, auth()->user()),
        );
    }
}