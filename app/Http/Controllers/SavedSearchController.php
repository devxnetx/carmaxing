<?php

namespace App\Http\Controllers;

use App\Enums\SearchScope;
use App\Models\SavedSearch;
use App\Services\SearchFilterHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedSearchController extends Controller
{
    public function __construct(
        private SearchFilterHelper $filterHelper,
    ) {}

    public function index(): View
    {
        $searches = auth()->user()
            ->savedSearches()
            ->latest()
            ->get();

        return view('saved-searches.index', compact('searches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'alert_enabled' => ['boolean'],
        ]);

        $filters = $this->filterHelper->filtersFromRequest($request);
        $filters['scope'] = SearchScope::fromRequest($request->input('scope'))->value;

        if (count($filters) === 1 && isset($filters['scope'])) {
            return back()->with('error', __('messages.saved_search_empty'));
        }

        auth()->user()->savedSearches()->create([
            'name' => $data['name'] ?: $this->defaultName($filters),
            'filters' => $filters,
            'alert_enabled' => $request->boolean('alert_enabled', true),
            'last_match_count' => 0,
        ]);

        return back()->with('success', __('messages.saved_search_created'));
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === auth()->id(), 403);
        $savedSearch->delete();

        return back()->with('success', __('messages.saved_search_deleted'));
    }

    public function toggleAlert(SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === auth()->id(), 403);
        $savedSearch->update(['alert_enabled' => ! $savedSearch->alert_enabled]);

        return back()->with('success', __('messages.saved_search_updated'));
    }

    /** @param array<string, mixed> $filters */
    private function defaultName(array $filters): string
    {
        $parts = [];

        if (! empty($filters['brand_id'])) {
            $parts[] = __('messages.search');
        }

        if ($this->filterHelper->hasExtendedFilters(Request::create('/', 'GET', $filters))) {
            $parts[] = __('messages.search_extended');
        }

        return $parts !== [] ? implode(' · ', $parts) : __('messages.saved_search_default_name');
    }
}