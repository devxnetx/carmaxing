@extends('layouts.admin')

@section('title', __('admin.nav_dashboard'))

@section('content')
<div class="w-full">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">{{ __('admin.dashboard_heading') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.dashboard_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-3 sm:p-4">
            <div class="text-[10px] text-[var(--color-text-muted)] sm:text-xs">{{ __('admin.stat_users') }}</div>
            <div class="mt-1 text-lg font-bold text-brand-600 sm:text-2xl">{{ number_format($stats['users_total']) }}</div>
            <div class="mt-1 text-xs text-[var(--color-text-muted)]">
                {{ $stats['users_company'] }} {{ __('admin.companies_short') }} · {{ $stats['users_private'] }} {{ __('admin.private_short') }}
            </div>
        </div>
        <div class="card p-3 sm:p-4">
            <div class="text-[10px] text-[var(--color-text-muted)] sm:text-xs">{{ __('admin.stat_listings') }}</div>
            <div class="mt-1 text-lg font-bold text-brand-600 sm:text-2xl">{{ number_format($stats['listings_published']) }}</div>
            <div class="mt-1 text-xs text-[var(--color-text-muted)]">
                {{ $stats['listings_total'] }} {{ __('admin.total') }} · {{ $stats['listings_archived'] }} {{ __('admin.archived') }}
            </div>
        </div>
        <div class="card p-3 sm:p-4">
            <div class="text-[10px] text-[var(--color-text-muted)] sm:text-xs">{{ __('admin.stat_verified_dealers') }}</div>
            <div class="mt-1 text-lg font-bold text-brand-600 sm:text-2xl">{{ number_format($stats['companies_verified']) }}</div>
            <div class="mt-1 text-xs text-[var(--color-text-muted)]">{{ $stats['companies_total'] }} {{ __('admin.companies_total') }}</div>
        </div>
        <div class="card p-3 sm:p-4">
            <div class="text-[10px] text-[var(--color-text-muted)] sm:text-xs">{{ __('admin.stat_pending_reports') }}</div>
            <div class="mt-1 text-lg font-bold sm:text-2xl {{ $stats['reports_pending'] ? 'text-red-600' : 'text-brand-600' }}">{{ number_format($stats['reports_pending']) }}</div>
            @if($stats['reports_pending'])
                <a href="{{ route('admin.reports.index') }}" class="mt-1 inline-block text-xs text-brand-600 hover:underline">{{ __('admin.review_reports') }}</a>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-admin-line-chart :title="__('admin.chart_new_users')" :data="$stats['new_users_chart']" :days="30" />
        <x-admin-line-chart :title="__('admin.chart_new_listings')" :data="$stats['new_listings_chart']" :days="30" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <div class="card p-4 lg:col-span-2">
            <h2 class="font-semibold">{{ __('admin.engagement') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <div class="text-xs text-[var(--color-text-muted)]">{{ __('messages.stats_views') }}</div>
                    <div class="text-xl font-bold">{{ number_format($stats['engagement']['views']) }}</div>
                </div>
                <div>
                    <div class="text-xs text-[var(--color-text-muted)]">{{ __('messages.stats_inquiries') }}</div>
                    <div class="text-xl font-bold">{{ number_format($stats['engagement']['inquiries']) }}</div>
                </div>
                <div>
                    <div class="text-xs text-[var(--color-text-muted)]">{{ __('messages.stats_phone_clicks') }}</div>
                    <div class="text-xl font-bold">{{ number_format($stats['engagement']['phone_clicks']) }}</div>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <h2 class="font-semibold">{{ __('admin.api_usage') }}</h2>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-[var(--color-text-muted)]">{{ __('admin.active_keys') }}</span>
                    <span class="font-medium">{{ $stats['api_keys_active'] }} / {{ $stats['api_keys_total'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[var(--color-text-muted)]">{{ __('admin.requests_today') }}</span>
                    <span class="font-medium">{{ number_format($stats['api_requests_today']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[var(--color-text-muted)]">{{ __('admin.requests_week') }}</span>
                    <span class="font-medium">{{ number_format($stats['api_requests_week']) }}</span>
                </div>
                @if($stats['imports_running'])
                    <div class="rounded-lg bg-amber-50 px-3 py-2 text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                        {{ $stats['imports_running'] }} {{ __('admin.imports_running') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">{{ __('admin.recent_listings') }}</h2>
                <a href="{{ route('admin.listings.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('admin.view_all') }}</a>
            </div>
            <div class="mt-4 divide-y divide-[var(--color-border)]">
                @foreach($stats['recent_listings'] as $listing)
                    <div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <x-admin-listing-thumb :listing="$listing" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.listings.edit', $listing) }}" class="truncate font-medium hover:text-brand-600">{{ $listing->title }}</a>
                                <div class="text-xs text-[var(--color-text-muted)]">{{ $listing->user?->name }} · {{ $listing->status->value }}</div>
                            </div>
                        </div>
                        <a href="{{ route('listings.show', $listing) }}" class="shrink-0 text-xs text-brand-600 hover:underline" target="_blank">{{ __('admin.view') }}</a>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">{{ __('admin.recent_users') }}</h2>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('admin.view_all') }}</a>
            </div>
            <div class="mt-4 divide-y divide-[var(--color-border)]">
                @foreach($stats['recent_users'] as $user)
                    <div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <a href="{{ route('admin.users.show', $user) }}" class="truncate font-medium hover:text-brand-600">{{ $user->name }}</a>
                            <div class="truncate text-xs text-[var(--color-text-muted)]">{{ $user->email }} · {{ $user->account_type?->value }}</div>
                        </div>
                        @if($user->isAdmin())
                            <span class="shrink-0 rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-medium text-brand-700 dark:bg-brand-950 dark:text-brand-300">Admin</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection