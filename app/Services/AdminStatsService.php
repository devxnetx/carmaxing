<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\ListingStatus;
use App\Models\ApiRequestLog;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Models\MobileBgImportRun;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdminStatsService
{
    public function dashboard(): array
    {
        $listingStats = Listing::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $apiRequestsToday = ApiRequestLog::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $apiRequestsWeek = ApiRequestLog::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'users_total' => User::query()->count(),
            'users_company' => User::query()->where('account_type', AccountType::Company)->count(),
            'users_private' => User::query()->where('account_type', AccountType::Private)->count(),
            'companies_total' => Company::query()->count(),
            'companies_verified' => Company::query()->where('is_verified', true)->whereNotNull('verified_at')->count(),
            'listings_published' => (int) ($listingStats[ListingStatus::Published->value] ?? 0),
            'listings_archived' => (int) ($listingStats[ListingStatus::Archived->value] ?? 0),
            'listings_draft' => (int) ($listingStats[ListingStatus::Draft->value] ?? 0),
            'listings_sold' => (int) ($listingStats[ListingStatus::Sold->value] ?? 0),
            'listings_total' => Listing::query()->count(),
            'engagement' => [
                'views' => (int) Listing::query()->sum('views_count'),
                'inquiries' => (int) Listing::query()->sum('inquiries_count'),
                'phone_clicks' => (int) Listing::query()->sum('phone_clicks_count'),
            ],
            'reports_pending' => ListingReport::query()->where('status', 'pending')->count(),
            'api_keys_active' => CompanyApiKey::query()->where('is_active', true)->count(),
            'api_keys_total' => CompanyApiKey::query()->count(),
            'api_requests_today' => $apiRequestsToday,
            'api_requests_week' => $apiRequestsWeek,
            'imports_running' => MobileBgImportRun::query()
                ->whereIn('status', [MobileBgImportRun::STATUS_PENDING, MobileBgImportRun::STATUS_RUNNING])
                ->count(),
            'recent_users' => User::query()->latest()->limit(5)->get(),
            'recent_listings' => Listing::query()
                ->with([
                    'brand',
                    'model.parent',
                    'user',
                    'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                ])
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'new_users_chart' => $this->dailyCountChart(User::class, 30),
            'new_listings_chart' => $this->dailyCountChart(Listing::class, 30),
            'api_usage_chart' => $this->dailyCountChart(ApiRequestLog::class, 14, 'created_at'),
        ];
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return array<int, array{date: string, count: int}>
     */
    private function dailyCountChart(string $model, int $days, string $dateColumn = 'created_at'): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $counts = $model::query()
            ->selectRaw("DATE({$dateColumn}) as day, COUNT(*) as count")
            ->where($dateColumn, '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day');

        $chart = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $chart[] = [
                'date' => $date,
                'count' => (int) ($counts[$date] ?? 0),
            ];
        }

        return $chart;
    }
}