@extends('layouts.admin')

@section('title', $user->name)

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-600 hover:underline">← {{ __('admin.nav_users') }}</a>
        <h1 class="mt-2 text-2xl font-bold">{{ $user->name }}</h1>
        <p class="text-sm text-[var(--color-text-muted)]">{{ $user->email }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-5">
                <h2 class="font-semibold">{{ __('admin.user_details') }}</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('admin.account_type') }}</dt><dd class="font-medium">{{ $user->account_type?->value }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.phone') }}</dt><dd class="font-medium">{{ $user->phone ?: '—' }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('admin.joined') }}</dt><dd class="font-medium">{{ $user->created_at?->format('d.m.Y H:i') }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('admin.listings') }}</dt><dd class="font-medium">{{ $user->listings_count }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.my_favorites') }}</dt><dd class="font-medium">{{ $user->favorites_count }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.saved_searches') }}</dt><dd class="font-medium">{{ $user->saved_searches_count }}</dd></div>
                </dl>
            </div>

            @if($user->company)
                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold">{{ __('messages.company_profile') }}</h2>
                        <a href="{{ route('admin.companies.show', $user->company) }}" class="text-sm text-brand-600 hover:underline">{{ __('admin.view') }}</a>
                    </div>
                    <p class="mt-2 font-medium">{{ $user->company->name }}</p>
                    @if($user->company->isVerifiedDealer())
                        <div class="mt-2"><x-verified-badge :company="$user->company" /></div>
                    @endif
                </div>
            @endif

            <div class="card p-5">
                <h2 class="font-semibold">{{ __('admin.recent_listings') }}</h2>
                <div class="mt-4 divide-y divide-[var(--color-border)]">
                    @forelse($listings as $listing)
                        <div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div>
                                <a href="{{ route('admin.listings.edit', $listing) }}" class="font-medium hover:text-brand-600">{{ $listing->title }}</a>
                                <div class="text-xs text-[var(--color-text-muted)]">{{ $listing->status->value }}</div>
                            </div>
                            <a href="{{ route('listings.show', $listing) }}" class="text-xs text-brand-600 hover:underline" target="_blank">{{ __('admin.view') }}</a>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--color-text-muted)]">{{ __('admin.no_listings') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="font-semibold">{{ __('admin.admin_access') }}</h2>
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-4">
                    @csrf
                    @method('PUT')
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_admin" value="0">
                        <input type="checkbox" name="is_admin" value="1" @checked($user->isAdmin()) @disabled($user->id === auth()->id())>
                        {{ __('admin.grant_admin') }}
                    </label>
                    @if($user->id !== auth()->id())
                        <button type="submit" class="btn-primary mt-4 w-full text-sm">{{ __('messages.save') }}</button>
                    @else
                        <p class="mt-2 text-xs text-[var(--color-text-muted)]">{{ __('admin.cannot_revoke_self') }}</p>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endsection