@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:py-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('messages.my_listings') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.dashboard_subtitle') }}</p>
        </div>
        <a href="{{ route('listings.create') }}" class="btn-primary w-full sm:w-auto">{{ __('messages.new_listing') }}</a>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('messages.stats_views') }}</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($totals['views']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('messages.stats_favorites') }}</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($totals['favorites']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('messages.stats_inquiries') }}</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($totals['inquiries']) }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('messages.stats_phone_clicks') }}</div>
            <div class="mt-1 text-2xl font-bold text-brand-600">{{ number_format($totals['phone_clicks']) }}</div>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-4">
        <a href="{{ route('listings.create') }}" class="card flex items-center gap-3 p-4 transition hover:border-brand-500">
            <x-icon name="plus" class="h-5 w-5 text-brand-600" />
            <span class="text-sm font-medium">{{ __('messages.new_listing') }}</span>
        </a>
        <a href="{{ route('favorites.index') }}" class="card flex items-center gap-3 p-4 transition hover:border-brand-500">
            <x-icon name="heart" class="h-5 w-5 text-brand-600" />
            <span class="text-sm font-medium">{{ __('messages.my_favorites') }}</span>
        </a>
        <a href="{{ route('saved-searches.index') }}" class="card flex items-center gap-3 p-4 transition hover:border-brand-500">
            <x-icon name="bell" class="h-5 w-5 text-brand-600" />
            <span class="text-sm font-medium">{{ __('messages.saved_searches') }}</span>
        </a>
        <a href="{{ route('search-history.index') }}" class="card flex items-center gap-3 p-4 transition hover:border-brand-500">
            <x-icon name="clock" class="h-5 w-5 text-brand-600" />
            <span class="text-sm font-medium">{{ __('messages.search_history') }}</span>
        </a>
        <a href="{{ route('settings') }}" class="card flex items-center gap-3 p-4 transition hover:border-brand-500">
            <x-icon name="cog" class="h-5 w-5 text-brand-600" />
            <span class="text-sm font-medium">{{ __('messages.settings') }}</span>
        </a>
    </div>

    @if($user->isCompany() && $user->company)
        <div class="card mt-6 flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-600 text-sm font-bold text-white">
                    @if($user->company->logoUrl())
                        <img src="{{ $user->company->logoUrl() }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ strtoupper(substr($user->company->name, 0, 2)) }}
                    @endif
                </div>
                <div>
                    <span class="font-medium">{{ $user->company->name }}</span>
                    <a href="{{ route('company.show', $user->company) }}" class="ml-2 text-sm text-brand-600 hover:underline">{{ __('messages.public_dealer_page') }} →</a>
                </div>
            </div>
            <a href="{{ route('company.edit') }}" class="btn-secondary text-sm">{{ __('messages.company_profile') }}</a>
        </div>
    @endif

    <div class="mt-6 space-y-3 md:hidden">
        @forelse($listingStats as $row)
            @php $listing = $row['listing']; $stats = $row['stats']; @endphp
            <div class="card p-4">
                <a href="{{ route('listings.show', $listing) }}" class="font-medium hover:text-brand-600">
                    {{ $listing->vehicleName() }}
                </a>
                @if($listing->ad_name)
                    <p class="text-sm text-[var(--color-text-muted)]">{{ $listing->ad_name }}</p>
                @endif
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-semibold text-brand-600">
                        @if($listing->price_on_request)
                            {{ __('messages.price_on_request') }}
                        @else
                            {{ number_format($listing->price) }} €
                        @endif
                    </span>
                    <span class="badge bg-[var(--color-surface-3)]">{{ $listing->status->value }}</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-[var(--color-text-muted)]">
                    <span>{{ __('messages.stats_views') }}: <strong class="text-[var(--color-text)]">{{ number_format($stats['views']) }}</strong></span>
                    <span>{{ __('messages.stats_favorites') }}: <strong class="text-[var(--color-text)]">{{ number_format($stats['favorites']) }}</strong></span>
                    <span>{{ __('messages.stats_inquiries') }}: <strong class="text-[var(--color-text)]">{{ number_format($stats['inquiries']) }}</strong></span>
                    <span>{{ __('messages.stats_phone_clicks') }}: <strong class="text-[var(--color-text)]">{{ number_format($stats['phone_clicks']) }}</strong></span>
                </div>
                <div class="mt-3 flex gap-3 text-sm">
                    <a href="{{ route('listings.edit', $listing) }}" class="text-brand-600 hover:underline">{{ __('messages.edit') }}</a>
                    <a href="{{ route('listings.show', $listing) }}" class="text-[var(--color-text-muted)] hover:underline">{{ __('messages.view') }}</a>
                </div>
            </div>
        @empty
            <div class="card p-8 text-center text-[var(--color-text-muted)]">{{ __('messages.no_listings_yet') }}</div>
        @endforelse
    </div>

    <div class="mt-6 hidden overflow-hidden rounded-xl border border-[var(--color-border)] md:block">
        <table class="w-full text-sm">
            <thead class="bg-[var(--color-surface-3)]">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('messages.model') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('messages.price_from') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('messages.stats_views') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('messages.stats_favorites') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('messages.stats_inquiries') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('messages.stats_phone_clicks') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('messages.status') }}</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--color-border)]">
                @forelse($listingStats as $row)
                    @php $listing = $row['listing']; $stats = $row['stats']; @endphp
                    <tr class="bg-[var(--color-surface)]">
                        <td class="px-4 py-3">
                            <a href="{{ route('listings.show', $listing) }}" class="font-medium hover:text-brand-600">
                                {{ $listing->vehicleName() }}@if($listing->ad_name)<span class="font-normal text-[var(--color-text-muted)]"> — {{ $listing->ad_name }}</span>@endif
                            </a>
                            <div class="text-xs text-[var(--color-text-muted)]">{{ $listing->brand->name }} · {{ $listing->year }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($listing->price_on_request)
                                {{ __('messages.price_on_request') }}
                            @else
                                {{ number_format($listing->price) }} €
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ number_format($stats['views']) }}</td>
                        <td class="px-4 py-3">{{ number_format($stats['favorites']) }}</td>
                        <td class="px-4 py-3">{{ number_format($stats['inquiries']) }}</td>
                        <td class="px-4 py-3">{{ number_format($stats['phone_clicks']) }}</td>
                        <td class="px-4 py-3">
                            <span class="badge bg-[var(--color-surface-3)]">{{ $listing->status->value }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('listings.edit', $listing) }}" class="text-brand-600 hover:underline">{{ __('messages.edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('messages.no_listings_yet') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection