@props(['listing', 'showFavorite' => true, 'favorited' => false])

@php
    use App\Support\ListingPresenter;
    $images = $listing->images;
    $mainImage = $images->first();
    $extraImages = $images->skip(1)->take(3);
    $photoCount = $images->count();
    $imageAlt = $listing->composeDisplayTitle();
    $featureExcerpt = $listing->relationLoaded('features')
        ? $listing->features->take(6)->pluck('name')->join(' · ')
        : null;
@endphp

<article class="card group overflow-hidden transition hover:border-brand-500/40 hover:shadow-md">
    <div class="flex flex-col gap-4 p-3 sm:flex-row sm:items-stretch sm:gap-5 sm:p-4">
        <a href="{{ route('listings.show', $listing) }}" class="listing-row-photos">
            <div class="listing-row-photos-main">
                @if($mainImage)
                    <x-listing-image
                        :image="$mainImage"
                        size="thumb"
                        :alt="$imageAlt"
                        class="absolute inset-0 h-full w-full object-cover"
                        :width="160"
                        :height="120"
                    />
                @else
                    <div class="flex h-full items-center justify-center px-2 text-center text-xs text-[var(--color-text-muted)]">{{ __('messages.no_photo') }}</div>
                @endif
                <x-new-ad-badge :listing="$listing" class="absolute left-1.5 top-1.5 z-10 sm:left-2 sm:top-2" />
                @if($photoCount > 0)
                    <span class="absolute bottom-1.5 left-1.5 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] text-white sm:text-xs">
                        <x-icon name="image" class="mr-0.5 inline h-3 w-3 sm:mr-1 sm:h-3.5 sm:w-3.5" />{{ $photoCount }}
                    </span>
                @endif
            </div>

            <div class="listing-row-photos-thumbs">
                @for($i = 0; $i < 3; $i++)
                    @php $thumb = $extraImages->values()->get($i); @endphp
                    <div class="listing-row-photos-thumb relative">
                        @if($thumb)
                            <x-listing-image
                                :image="$thumb"
                                size="thumb"
                                alt=""
                                class="absolute inset-0 h-full w-full object-cover"
                                :width="52"
                                :height="40"
                            />
                        @endif
                    </div>
                @endfor
            </div>
        </a>

        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <div class="flex items-start justify-between gap-3">
                <a href="{{ route('listings.show', $listing) }}" class="min-w-0 flex-1">
                    <h3 class="line-clamp-2 text-base font-semibold leading-snug group-hover:text-brand-600">
                        {{ $listing->vehicleName() }}
                    </h3>
                </a>

                <div class="shrink-0 text-right leading-tight">
                    @if($listing->price_on_request)
                        <div class="text-sm font-bold text-brand-600 sm:text-base">{{ __('messages.price_on_request') }}</div>
                    @else
                        <div class="text-lg font-bold text-brand-600 sm:text-xl">{{ number_format($listing->price) }} {{ __('messages.eur') }}</div>
                        <div class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ number_format($listing->priceInBgn()) }} {{ __('messages.bgn') }}</div>
                    @endif
                </div>
            </div>

            <p class="text-sm text-[var(--color-text-muted)]">{{ ListingPresenter::specLine($listing) }}</p>

            @if($featureExcerpt)
                <p class="mt-1.5 line-clamp-2 text-xs text-[var(--color-text-muted)]">{{ $featureExcerpt }}</p>
            @endif

            <x-listing-card-dates :listing="$listing" class="mt-1.5" />

            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[var(--color-text-muted)]">
                    @if($listing->locationLabel())
                        <span>
                            <x-icon name="map-pin" class="mr-1 inline h-3.5 w-3.5 text-brand-600/80" />
                            {{ $listing->locationLabel() }}
                        </span>
                    @endif
                    @if($listing->displayAdNumber())
                        <span>#{{ $listing->displayAdNumber() }}</span>
                    @endif
                    @if($listing->company)
                        <a href="{{ route('company.show', $listing->company) }}" class="hover:text-brand-600" @click.stop>{{ $listing->company->name }}</a>
                    @elseif($listing->relationLoaded('user'))
                        <span>{{ __('messages.private_seller') }}</span>
                    @endif
                    @if($listing->price_negotiable)
                        <span class="badge bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">{{ __('messages.negotiable') }}</span>
                    @endif
            </div>
        </div>

        @if($showFavorite)
            <div class="flex shrink-0 items-start gap-1 sm:pt-1">
                <div x-data="compareButton('{{ $listing->slug }}', '{{ route('compare.add', $listing) }}')">
                    <button
                        type="button"
                        @click="add()"
                        :disabled="loading"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-[var(--color-border)] text-[var(--color-text-muted)] transition hover:border-brand-500 hover:bg-[var(--color-surface-3)]"
                        title="{{ __('messages.compare_add') }}"
                    >
                        <x-icon name="compare" class="h-5 w-5" />
                    </button>
                </div>
                <div
                    x-data="favoriteButton('{{ $listing->slug }}', {{ $favorited ? 'true' : 'false' }}, {{ auth()->check() ? 'true' : 'false' }}, '{{ route('login') }}')"
                    class="flex flex-col items-center"
                >
                    <button
                        type="button"
                        @click="toggle()"
                        :disabled="loading"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-[var(--color-border)] transition hover:border-brand-500 hover:bg-[var(--color-surface-3)]"
                        :class="favorited ? 'text-red-500 border-red-200 dark:border-red-900' : 'text-[var(--color-text-muted)]'"
                        :title="favorited ? '{{ __('messages.remove_from_favorites') }}' : '{{ __('messages.add_to_favorites') }}'"
                    >
                        <x-icon name="heart" variant="solid" class="h-5 w-5" x-show="favorited" x-cloak />
                        <x-icon name="heart" class="h-5 w-5" x-show="!favorited" />
                    </button>
                </div>
            </div>
        @endif
    </div>
</article>