<?php

namespace App\Http\Controllers;

use App\Models\SearchHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SearchHistoryController extends Controller
{
    public function index(): View
    {
        $searches = auth()->user()
            ->searchHistories()
            ->orderByDesc('searched_at')
            ->get();

        return view('search-history.index', compact('searches'));
    }

    public function destroy(SearchHistory $searchHistory): RedirectResponse
    {
        abort_unless($searchHistory->user_id === auth()->id(), 403);
        $searchHistory->delete();

        return back()->with('success', __('messages.search_history_deleted'));
    }

    public function destroyAll(): RedirectResponse
    {
        auth()->user()->searchHistories()->delete();

        return back()->with('success', __('messages.search_history_cleared'));
    }
}