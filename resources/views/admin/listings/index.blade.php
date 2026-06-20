@extends('layouts.admin')

@section('title', __('admin.nav_listings'))

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('admin.nav_listings') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.listings_subtitle') }}</p>
    </div>

    <x-admin-filters :action="route('admin.listings.index')">
        <div class="min-w-[200px] flex-1">
            <label class="label">{{ __('admin.search') }}</label>
            <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('admin.search_listings_placeholder') }}">
        </div>
        <div>
            <label class="label">{{ __('admin.status') }}</label>
            <select name="status" class="input">
                <option value="">{{ __('admin.all') }}</option>
                @foreach(['published', 'archived', 'draft', 'sold'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
    </x-admin-filters>

    <x-admin-table :headers="['', __('admin.listing'), __('admin.owner'), __('admin.status'), __('messages.views'), __('admin.updated'), '']">
        @forelse($listings as $listing)
            <tr class="hover:bg-[var(--color-surface-3)]">
                <td class="w-28 px-4 py-3">
                    <x-admin-listing-thumb :listing="$listing" />
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.listings.edit', $listing) }}" class="font-medium hover:text-brand-600">{{ $listing->title }}</a>
                    @if($listing->company)
                        <div class="text-xs text-[var(--color-text-muted)]">{{ $listing->company->name }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $listing->user?->name }}</td>
                <td class="px-4 py-3">
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-medium',
                        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' => $listing->status->value === 'published',
                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => $listing->status->value === 'archived',
                        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => in_array($listing->status->value, ['draft', 'sold']),
                    ])>{{ $listing->status->value }}</span>
                </td>
                <td class="px-4 py-3">{{ number_format($listing->views_count) }}</td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $listing->updated_at?->format('d.m.Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.listings.edit', $listing) }}" class="text-sm text-brand-600 hover:underline">{{ __('messages.edit') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.no_results') }}</td></tr>
        @endforelse
    </x-admin-table>

    <div class="mt-4">{{ $listings->links() }}</div>
</div>
@endsection