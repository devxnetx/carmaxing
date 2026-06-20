<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\VehicleBrand;
use App\Services\Tenders\TenderBidService;
use App\Services\Tenders\TenderPersistenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyTenderController extends Controller
{
    public function __construct(
        private TenderPersistenceService $persistence,
        private TenderBidService $bidService,
    ) {}

    public function index(Request $request): View
    {
        $tenders = Tender::query()
            ->where('user_id', $request->user()->id)
            ->with(['brand', 'model.parent', 'images'])
            ->latest()
            ->paginate(15);

        return view('tenders.my.index', compact('tenders'));
    }

    public function create(): View
    {
        return view('tenders.my.create', [
            'brands' => VehicleBrand::query()->orderBy('name')->get(),
            'regions' => Region::query()->orderBy('sort_order')->get(),
            'durationOptions' => $this->persistence->durationOptions(),
            'bidIncrements' => config('tenders.allowed_bid_increments', [100]),
            'defaultBidIncrement' => config('tenders.default_bid_increment', 100),
            'maxDurationDays' => config('tenders.max_duration_days'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tender = $this->persistence->create($request, $request->user());

        return redirect()
            ->route('my.tenders.manage', $tender)
            ->with('success', __('tenders.created'));
    }

    public function manage(Tender $tender): View
    {
        abort_unless($tender->isOwnedBy(auth()->user()), 403);

        $tender->load([
            'brand',
            'model.parent',
            'images',
            'region',
            'company',
            'bids.user',
            'winningBid.user',
        ]);

        $bids = $tender->bids()
            ->with('user')
            ->orderByDesc('amount')
            ->get();

        return view('tenders.my.manage', compact('tender', 'bids'));
    }

    public function award(Tender $tender, TenderBid $bid): RedirectResponse
    {
        abort_unless($tender->isOwnedBy(auth()->user()), 403);
        abort_unless((int) $bid->tender_id === (int) $tender->id, 404);

        $this->bidService->award($tender, $bid);

        return redirect()
            ->route('my.tenders.manage', $tender)
            ->with('success', __('tenders.awarded_success'));
    }
}