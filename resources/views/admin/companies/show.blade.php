@extends('layouts.admin')

@section('title', $company->name)

@section('content')
<div class="mx-auto max-w-5xl" x-data="{ editOpen: false }">
    <div class="mb-6">
        <a href="{{ route('admin.companies.index') }}" class="text-sm text-brand-600 hover:underline">← {{ __('admin.nav_companies') }}</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold">{{ $company->name }}</h1>
            @if($company->isVerifiedDealer())
                <x-verified-badge :company="$company" />
            @endif
        </div>
        <p class="text-sm text-[var(--color-text-muted)]">{{ $company->email }} · {{ $company->phone }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-5">
                <h2 class="font-semibold">{{ __('admin.company_details') }}</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('admin.owner') }}</dt><dd><a href="{{ route('admin.users.show', $company->user) }}" class="text-brand-600 hover:underline">{{ $company->user?->name }}</a></dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.address') }}</dt><dd>{{ $company->address ?: '—' }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.website') }}</dt><dd>@if($company->website)<a href="{{ $company->website }}" class="text-brand-600 hover:underline" target="_blank">{{ $company->website }}</a>@else — @endif</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('admin.listings') }}</dt><dd>{{ $company->listings_count }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">{{ __('admin.api_keys') }}</dt><dd>{{ $company->api_keys_count }}</dd></div>
                    <div><dt class="text-[var(--color-text-muted)]">Mobile.bg</dt><dd>{{ $company->mobile_bg_url ?: '—' }}</dd></div>
                </dl>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="button" class="btn-secondary text-sm" @click="editOpen = true">{{ __('admin.edit_company_profile') }}</button>
                    <a href="{{ route('company.show', $company) }}" class="text-sm text-brand-600 hover:underline" target="_blank">{{ __('messages.public_dealer_page') }} →</a>
                </div>
            </div>

            <div class="card p-5">
                <h2 class="font-semibold">{{ __('admin.recent_listings') }}</h2>
                <div class="mt-4 divide-y divide-[var(--color-border)]">
                    @forelse($recentListings as $listing)
                        <div class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <a href="{{ route('admin.listings.edit', $listing) }}" class="font-medium hover:text-brand-600">{{ $listing->title }}</a>
                            <span class="text-xs text-[var(--color-text-muted)]">{{ $listing->status->value }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-[var(--color-text-muted)]">{{ __('admin.no_listings') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="font-semibold">{{ __('admin.verification_badge') }}</h2>
                <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('admin.verification_help') }}</p>
                <form method="POST" action="{{ route('admin.companies.verification', $company) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    @if($company->isVerifiedDealer())
                        <input type="hidden" name="is_verified" value="0">
                        <button type="submit" class="btn-secondary w-full text-sm">{{ __('admin.revoke_verification') }}</button>
                    @else
                        <input type="hidden" name="is_verified" value="1">
                        <button type="submit" class="btn-primary w-full text-sm">{{ __('admin.grant_verification') }}</button>
                    @endif
                </form>
                @if($company->verified_at)
                    <p class="mt-3 text-xs text-[var(--color-text-muted)]">{{ __('admin.verified_since', ['date' => $company->verified_at->format('d.m.Y')]) }}</p>
                @endif
            </div>

            <div class="card p-5">
                <h2 class="font-semibold">{{ __('admin.grant_api_key') }}</h2>
                <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('admin.grant_api_key_help') }}</p>

                @if(session('new_api_key'))
                    <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs dark:border-amber-700 dark:bg-amber-950">
                        <p class="font-medium">{{ __('admin.api_key_granted') }}</p>
                        <p class="mt-2 break-all font-mono">{{ session('new_api_key') }}</p>
                    </div>
                @endif

                @if($activeApiKey)
                    <div class="mt-3 rounded-lg border border-[var(--color-border)] p-3 text-sm">
                        <div class="font-mono text-xs">{{ $activeApiKey->key_prefix }}…</div>
                        <div class="mt-1 text-xs text-[var(--color-text-muted)]">
                            {{ __('admin.active') }}
                            @if($activeApiKey->last_used_at) · {{ $activeApiKey->last_used_at->diffForHumans() }} @endif
                        </div>
                        <form method="POST" action="{{ route('admin.api-keys.revoke', $activeApiKey) }}" class="mt-2" onsubmit="return confirm(@js(__('admin.revoke_key_confirm')))">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('admin.revoke') }}</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.companies.api-keys.generate', $company) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn-primary w-full text-sm">{{ __('admin.generate_api_key') }}</button>
                    </form>
                @endif
            </div>

            <div class="card p-5">
                <h2 class="font-semibold">{{ __('messages.mobile_bg_profile_extract') }}</h2>
                <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('admin.mobile_profile_extract_help') }}</p>
                <form method="POST" action="{{ route('admin.companies.mobile-bg-profile', $company) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label text-xs" for="mobile_bg_profile_url">{{ __('messages.mobile_bg_profile_url') }}</label>
                        <input
                            type="url"
                            name="mobile_bg_profile_url"
                            id="mobile_bg_profile_url"
                            class="input text-sm"
                            placeholder="https://ratola.mobile.bg/"
                            value="{{ old('mobile_bg_profile_url', $company->mobile_bg_url) }}"
                            required
                        >
                    </div>
                    <button type="submit" class="btn-primary w-full text-sm">{{ __('messages.mobile_bg_profile_extract_button') }}</button>
                </form>
            </div>

            <div class="card p-5" x-data="mobileBgImport({{ $latestImport && ! $latestImport->isFinished() ? $latestImport->id : 'null' }}, @js($latestImport ? route('admin.companies.mobile-bg-import.status', [$company, $latestImport]) : ''))">
                <h2 class="font-semibold">{{ __('messages.mobile_bg_import') }}</h2>
                <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('admin.mobile_import_help') }}</p>

                @if($company->mobile_bg_last_sync_at)
                    <p class="mt-2 text-xs text-[var(--color-text-muted)]">
                        {{ __('messages.mobile_bg_last_sync') }}: {{ $company->mobile_bg_last_sync_at->format('d.m.Y H:i') }}
                    </p>
                @endif

                @if($latestImport)
                    <div class="mt-3 rounded-lg border border-[var(--color-border)] p-3 text-xs">
                        <div class="font-medium">{{ __('messages.mobile_bg_import_status') }}:
                            @if($latestImport->status === 'completed')
                                {{ __('messages.mobile_bg_import_completed') }}
                            @elseif($latestImport->status === 'failed')
                                {{ __('messages.mobile_bg_import_failed') }}
                            @elseif($latestImport->status === 'running')
                                {{ __('messages.mobile_bg_import_running_status') }}
                            @else
                                {{ __('messages.mobile_bg_import_pending') }}
                            @endif
                        </div>
                        <dl class="mt-2 grid grid-cols-2 gap-2">
                            <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.mobile_bg_found') }}</dt><dd x-text="status?.total_found ?? {{ $latestImport->total_found }}">{{ $latestImport->total_found }}</dd></div>
                            <div><dt class="text-[var(--color-text-muted)]">{{ __('messages.mobile_bg_created') }}</dt><dd x-text="status?.created_count ?? {{ $latestImport->created_count }}">{{ $latestImport->created_count }}</dd></div>
                        </dl>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.companies.mobile-bg-import', $company) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label text-xs" for="mobile_bg_import_url">{{ __('messages.mobile_bg_import_url') }}</label>
                        <input
                            type="url"
                            name="mobile_bg_url"
                            id="mobile_bg_import_url"
                            class="input text-sm"
                            placeholder="https://ratola.mobile.bg/"
                            value="{{ old('mobile_bg_url', $company->mobile_bg_url) }}"
                            required
                        >
                    </div>
                    <label class="flex items-center gap-2 text-xs">
                        <input type="checkbox" name="sync_images" value="1" checked>
                        {{ __('messages.mobile_bg_sync_images') }}
                    </label>
                    <button type="submit" class="btn-primary w-full text-sm" :disabled="polling">{{ __('messages.mobile_bg_start_import') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div x-show="editOpen" x-cloak class="fixed inset-0 z-[70]" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50" @click="editOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div
                x-show="editOpen"
                x-transition
                @click.outside="editOpen = false"
                class="card w-full max-w-md p-5 shadow-xl"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ __('admin.edit_company_profile') }}</h2>
                        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.edit_company_profile_help') }}</p>
                    </div>
                    <button type="button" class="text-[var(--color-text-muted)] hover:text-[var(--color-text)]" @click="editOpen = false" aria-label="{{ __('messages.close') }}">×</button>
                </div>

                <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="label" for="company_name">{{ __('admin.name') }}</label>
                        <input type="text" name="name" id="company_name" class="input" value="{{ old('name', $company->name) }}" required>
                    </div>
                    <div>
                        <label class="label">{{ __('messages.phone') }}</label>
                        <x-phone-input name="phone" :value="$company->phone" :required="true" />
                    </div>
                    <div>
                        <label class="label" for="company_email">{{ __('admin.email') }}</label>
                        <input type="email" name="email" id="company_email" class="input" value="{{ old('email', $company->email) }}">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="btn-secondary text-sm" @click="editOpen = false">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="btn-primary text-sm">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection