<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = ListingReport::query()
            ->with([
                'listing.brand',
                'listing.model.parent',
                'listing.user',
                'listing.images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
                'user',
                'reviewer',
            ])
            ->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'pending');
        }

        $reports = $query->paginate(20)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function resolve(Request $request, ListingReport $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:resolved,dismissed'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'archive_listing' => ['boolean'],
        ]);

        $report->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($request->boolean('archive_listing')) {
            $report->listing?->archive();
        }

        return back()->with('success', __('admin.report_reviewed'));
    }
}