@extends('layouts.admin')

@section('title', __('admin.nav_reports'))

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('admin.nav_reports') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.reports_subtitle') }}</p>
    </div>

    <x-admin-filters :action="route('admin.reports.index')">
        <div>
            <label class="label">{{ __('admin.status') }}</label>
            <select name="status" class="input">
                <option value="pending" @selected(request('status', 'pending') === 'pending')>{{ __('admin.pending') }}</option>
                <option value="resolved" @selected(request('status') === 'resolved')>{{ __('admin.resolved') }}</option>
                <option value="dismissed" @selected(request('status') === 'dismissed')>{{ __('admin.dismissed') }}</option>
            </select>
        </div>
    </x-admin-filters>

    <div class="space-y-4">
        @forelse($reports as $report)
            <div class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 flex-1 gap-4">
                        @if($report->listing)
                            <x-admin-listing-thumb :listing="$report->listing" />
                        @endif
                        <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-950 dark:text-red-200">{{ $report->reason }}</span>
                            <span class="text-xs text-[var(--color-text-muted)]">{{ $report->created_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        <a href="{{ route('admin.listings.edit', $report->listing) }}" class="mt-2 block font-medium hover:text-brand-600">{{ $report->listing?->title }}</a>
                        <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ $report->details ?: '—' }}</p>
                        <p class="mt-2 text-xs text-[var(--color-text-muted)]">
                            {{ __('admin.reported_by') }}: {{ $report->user?->name ?: __('admin.anonymous') }}
                        </p>
                        </div>
                    </div>
                    <a href="{{ route('listings.show', $report->listing) }}" class="text-sm text-brand-600 hover:underline" target="_blank">{{ __('admin.view_public') }}</a>
                </div>

                @if($report->isPending())
                    <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="mt-4 border-t border-[var(--color-border)] pt-4">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div>
                                <label class="label">{{ __('admin.admin_notes') }}</label>
                                <textarea name="admin_notes" rows="3" class="input" placeholder="{{ __('admin.admin_notes_placeholder') }}"></textarea>
                            </div>
                            <div class="space-y-3">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="archive_listing" value="1">
                                    {{ __('admin.archive_listing_on_resolve') }}
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" name="status" value="resolved" class="btn-primary text-sm">{{ __('admin.mark_resolved') }}</button>
                                    <button type="submit" name="status" value="dismissed" class="btn-secondary text-sm">{{ __('admin.dismiss') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="mt-4 border-t border-[var(--color-border)] pt-4 text-sm">
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-green-100 text-green-800' => $report->status === 'resolved',
                            'bg-gray-100 text-gray-700' => $report->status === 'dismissed',
                        ])>{{ $report->status }}</span>
                        @if($report->admin_notes)
                            <p class="mt-2 text-[var(--color-text-muted)]">{{ $report->admin_notes }}</p>
                        @endif
                        @if($report->reviewed_at)
                            <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('admin.reviewed_at', ['date' => $report->reviewed_at->format('d.m.Y H:i')]) }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="card p-8 text-center text-[var(--color-text-muted)]">{{ __('admin.no_reports') }}</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $reports->links() }}</div>
</div>
@endsection