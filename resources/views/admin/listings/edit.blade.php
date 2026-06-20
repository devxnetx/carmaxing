@extends('layouts.admin')

@section('title', __('admin.edit_listing'))

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('admin.listings.index') }}" class="text-sm text-brand-600 hover:underline">← {{ __('admin.nav_listings') }}</a>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">{{ __('admin.edit_listing') }}</h1>
                <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ $listing->title }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('listings.show', $listing) }}" class="btn-secondary text-sm" target="_blank">{{ __('admin.view_public') }}</a>
                @if($listing->status->value !== 'published')
                    <form method="POST" action="{{ route('admin.listings.publish', $listing) }}">
                        @csrf
                        <button type="submit" class="btn-primary text-sm">{{ __('admin.publish') }}</button>
                    </form>
                @endif
                @if($listing->status->value !== 'archived')
                    <form method="POST" action="{{ route('admin.listings.archive', $listing) }}" onsubmit="return confirm(@js(__('admin.archive_confirm')))">
                        @csrf
                        <button type="submit" class="btn-secondary text-sm text-red-600">{{ __('admin.archive') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-6 p-4">
        <div class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><span class="text-[var(--color-text-muted)]">{{ __('admin.owner') }}:</span> <a href="{{ route('admin.users.show', $listing->user) }}" class="text-brand-600 hover:underline">{{ $listing->user?->name }}</a></div>
            <div><span class="text-[var(--color-text-muted)]">{{ __('admin.status') }}:</span> {{ $listing->status->value }}</div>
            <div><span class="text-[var(--color-text-muted)]">{{ __('messages.views') }}:</span> {{ number_format($listing->views_count) }}</div>
            <div><span class="text-[var(--color-text-muted)]">{{ __('messages.stats_phone_clicks') }}:</span> {{ number_format($listing->phone_clicks_count) }}</div>
        </div>
        <form method="POST" action="{{ route('admin.listings.status', $listing) }}" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            @method('PUT')
            <div>
                <label class="label">{{ __('admin.change_status') }}</label>
                <select name="status" class="input">
                    @foreach(['published', 'archived', 'draft', 'sold'] as $status)
                        <option value="{{ $status }}" @selected($listing->status->value === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-secondary text-sm">{{ __('admin.update_status') }}</button>
        </form>
    </div>

    @include('listings._form-body', [
        'formAction' => route('admin.listings.update', $listing),
        'cancelUrl' => route('admin.listings.index'),
        'submitLabel' => __('messages.save'),
    ])
</div>
@endsection