<?php

namespace App\Http\Controllers;

use App\Services\ListingAnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private ListingAnalyticsService $analytics,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $listingStats = $this->analytics->statsForUser($user);
        $totals = [
            'views' => $listingStats->sum(fn ($row) => $row['stats']['views']),
            'favorites' => $listingStats->sum(fn ($row) => $row['stats']['favorites']),
            'inquiries' => $listingStats->sum(fn ($row) => $row['stats']['inquiries']),
            'phone_clicks' => $listingStats->sum(fn ($row) => $row['stats']['phone_clicks']),
        ];

        return view('dashboard.index', compact('listingStats', 'totals', 'user'));
    }
}