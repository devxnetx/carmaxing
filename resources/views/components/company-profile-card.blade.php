@props([
    'company',
    'listingsCount' => null,
    'phone' => null,
    'phoneClickUrl' => null,
    'linked' => true,
    'pageTitle' => false,
    'showCover' => true,
])

@php
    $displayPhone = $phone ?? $company->phone;
    $profileUrl = route('company.show', $company);
@endphp

<div class="card overflow-hidden">
    @if($showCover)
        <div class="relative aspect-[3/1] min-h-[7rem] bg-gradient-to-r from-brand-700 to-brand-500 sm:min-h-[9rem]">
            @if($company->coverUrl())
                <img src="{{ $company->coverUrl() }}" alt="" class="h-full w-full object-cover">
            @endif
            @if($linked)
                <a href="{{ $profileUrl }}" class="absolute inset-0" aria-label="{{ $company->name }}"></a>
            @endif
        </div>
    @endif

    <div class="px-4 py-5 sm:px-6 sm:py-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-5">
            <div class="flex min-w-0 flex-1 items-center gap-4">
                <div class="shrink-0">
                    @if($linked)
                        <a href="{{ $profileUrl }}" class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border border-[var(--color-border)] bg-brand-600 text-xl font-bold text-white shadow-sm transition hover:opacity-90 sm:h-20 sm:w-20 sm:text-2xl">
                            @if($company->logoUrl())
                                <img src="{{ $company->logoUrl() }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ strtoupper(substr($company->name, 0, 2)) }}
                            @endif
                        </a>
                    @else
                        <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border border-[var(--color-border)] bg-brand-600 text-xl font-bold text-white shadow-sm sm:h-20 sm:w-20 sm:text-2xl">
                            @if($company->logoUrl())
                                <img src="{{ $company->logoUrl() }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ strtoupper(substr($company->name, 0, 2)) }}
                            @endif
                        </div>
                    @endif
                </div>

                <div class="min-w-0">
                    @if($linked)
                        <a href="{{ $profileUrl }}" class="text-xl font-bold hover:text-brand-600 sm:text-2xl">{{ $company->name }}</a>
                    @elseif($pageTitle)
                        <h1 class="text-xl font-bold sm:text-2xl">{{ $company->name }}</h1>
                    @else
                        <h2 class="text-xl font-bold sm:text-2xl">{{ $company->name }}</h2>
                    @endif
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <x-verified-badge :company="$company" />
                        @if($company->member_since_year)
                            <span class="text-sm text-[var(--color-text-muted)]">{{ __('messages.member_since') }} {{ $company->member_since_year }}</span>
                        @endif
                        @if($listingsCount !== null)
                            <span class="text-sm text-[var(--color-text-muted)]">· {{ number_format($listingsCount) }} {{ __('messages.results') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($displayPhone)
                <x-phone-reveal-button
                    :phone="$displayPhone"
                    :phone-click-url="$phoneClickUrl"
                    class="btn-primary w-full shrink-0 sm:w-auto"
                />
            @endif
        </div>

        @if($company->city || $company->region || $company->email || $company->website)
            <div class="mt-6 grid gap-4 border-t border-[var(--color-border)] pt-6 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @if($company->city || $company->region || $company->address)
                    <div>
                        <div class="text-[var(--color-text-muted)]">{{ __('messages.location') }}</div>
                        <div class="mt-1 font-medium">
                            @if($company->address){{ $company->address }}, @endif
                            {{ $company->city }}@if($company->region), {{ $company->region->name }}@endif
                        </div>
                    </div>
                @endif
                @if($company->email)
                    <div>
                        <div class="text-[var(--color-text-muted)]">Email</div>
                        <a href="mailto:{{ $company->email }}" class="mt-1 block font-medium text-brand-600 hover:underline">{{ $company->email }}</a>
                    </div>
                @endif
                @if($company->website)
                    <div>
                        <div class="text-[var(--color-text-muted)]">{{ __('messages.website') }}</div>
                        <a href="{{ $company->website }}" target="_blank" rel="noopener" class="mt-1 block font-medium text-brand-600 hover:underline">{{ parse_url($company->website, PHP_URL_HOST) ?: $company->website }}</a>
                    </div>
                @endif
            </div>
        @endif

        @if($company->description)
            <div class="mt-6">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">{{ __('messages.about_us') }}</h3>
                <p class="mt-2 leading-relaxed text-[var(--color-text-muted)]">{{ $company->description }}</p>
            </div>
        @endif

        @if($linked && ! $pageTitle)
            <div class="mt-6">
                <a href="{{ $profileUrl }}" class="text-sm font-medium text-brand-600 hover:underline">
                    {{ __('messages.public_dealer_page') }} →
                </a>
            </div>
        @endif
    </div>
</div>