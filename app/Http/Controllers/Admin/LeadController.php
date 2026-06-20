<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadContactedStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ExtractLeadsFromMobileBg;
use App\Jobs\SyncLeadListingCounts;
use App\Mail\LeadInviteMail;
use App\Models\Lead;
use App\Models\LeadExtractionRun;
use App\Services\MobileBg\MobileBgDealersDirectoryScraper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString() ?: 'cars_desc';

        $query = Lead::query()
            ->with(['company', 'region']);

        $this->applySort($query, $sort);

        if ($city = $request->string('city')->trim()->toString()) {
            $query->where('source_city', $city);
        }

        if ($request->string('contacted')->toString() === LeadContactedStatus::PendingInvite->value) {
            $query->where('contacted_status', LeadContactedStatus::PendingInvite);
        } elseif ($request->string('contacted')->toString() === LeadContactedStatus::EmailSent->value) {
            $query->where('contacted_status', LeadContactedStatus::EmailSent);
        }

        if ($request->string('onboarded')->toString() === '1') {
            $query->whereNotNull('company_id');
        } elseif ($request->string('onboarded')->toString() === '0') {
            $query->whereNull('company_id');
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('mobile_bg_url', 'like', "%{$search}%");
            });
        }

        $leads = $query->paginate(25)->withQueryString();

        $cities = Lead::query()
            ->whereNotNull('source_city')
            ->distinct()
            ->orderBy('source_city')
            ->pluck('source_city');

        $activeRun = LeadExtractionRun::query()
            ->whereIn('status', [LeadExtractionRun::STATUS_PENDING, LeadExtractionRun::STATUS_RUNNING])
            ->latest()
            ->first();

        $recentRuns = LeadExtractionRun::query()->latest()->limit(5)->get();

        return view('admin.leads.index', compact('leads', 'cities', 'activeRun', 'recentRuns', 'sort'));
    }

    private function applySort(\Illuminate\Database\Eloquent\Builder $query, string $sort): void
    {
        match ($sort) {
            'cars_asc' => $query->orderBy('listings_count')->orderBy('name'),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'newest' => $query->latest('extracted_at')->latest('id'),
            'oldest' => $query->oldest('extracted_at')->oldest('id'),
            default => $query->orderByDesc('listings_count')->orderBy('name'),
        };
    }

    public function store(Request $request, MobileBgDealersDirectoryScraper $directoryScraper): RedirectResponse
    {
        $data = $request->validate([
            'source_url' => ['required', 'string', 'max:500'],
        ]);

        $activeRun = LeadExtractionRun::query()
            ->whereIn('status', [LeadExtractionRun::STATUS_PENDING, LeadExtractionRun::STATUS_RUNNING])
            ->exists();

        if ($activeRun) {
            return back()->with('error', __('admin.lead_extraction_running'));
        }

        try {
            $normalizedUrl = $directoryScraper->normalizeDirectoryUrl($data['source_url']);
            $city = $directoryScraper->parseCityFromUrl($normalizedUrl);
        } catch (\InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        $run = LeadExtractionRun::query()->create([
            'source_url' => $normalizedUrl,
            'city_slug' => $city['slug'],
            'city_label' => $city['label'],
            'status' => LeadExtractionRun::STATUS_PENDING,
        ]);

        $job = new ExtractLeadsFromMobileBg($run);

        if (config('queue.default') === 'sync') {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }

        $message = config('queue.default') === 'sync'
            ? __('admin.lead_extraction_completed_sync')
            : __('admin.lead_extraction_started');

        return back()->with('success', $message);
    }

    public function refreshListingCounts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'city' => ['nullable', 'string', 'max:255'],
        ]);

        $job = new SyncLeadListingCounts($data['city'] ?? null);

        if (config('queue.default') === 'sync') {
            dispatch_sync($job);
            $message = __('admin.lead_counts_refreshed_sync');
        } else {
            dispatch($job);
            $message = __('admin.lead_counts_refresh_started');
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $lead->update([
            'email' => $data['email'] ?: null,
        ]);

        return back()->with('success', __('admin.lead_updated'));
    }

    public function sendInvite(Lead $lead): RedirectResponse
    {
        if ($lead->isOnboarded()) {
            return back()->with('error', __('admin.lead_already_onboarded'));
        }

        if (! $lead->email) {
            return back()->with('error', __('admin.lead_no_email'));
        }

        Mail::to($lead->email)->send(new LeadInviteMail($lead));

        $lead->update([
            'contacted_status' => LeadContactedStatus::EmailSent,
            'contacted_at' => now(),
        ]);

        return back()->with('success', __('admin.lead_invite_sent', ['email' => $lead->email]));
    }
}