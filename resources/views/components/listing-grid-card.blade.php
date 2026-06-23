@props(['listing', 'showFavorite' => true, 'favorited' => false])

@php
    use App\Support\ListingPresenter;
    $images = $listing->images;
    $mainImage = $images->first();
    $extraImages = $images->skip(1)->take(3);
    $photoCount = $images->count();
    $imageAlt = $listing->vehicleName();
    $featureExcerpt = $listing->relationLoaded('features')
        ? $listing->features->take(5)->pluck('name')->join(' · ')
        : null;
@endphp

<article class="listing-grid-card card group flex h-full flex-col overflow-hidden transition hover:border-brand-500/40 hover:shadow-md">
    <div class="relative">
        <a href="{{ route('listings.show', $listing) }}" class="listing-grid-card-photos block w-full">
            <div class="listing-grid-card-photos-main">
                @if($mainImage)
                    <x-listing-image
                        :image="$mainImage"
                        size="medium"
                        :alt="$imageAlt"
                        class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                        :width="400"
                        :height="300"
                    />
                @else
                    <div class="flex h-full items-center justify-center text-xs text-[var(--color-text-muted)]">{{ __('messages.no_photo') }}</div>
                @endif
                <x-new-ad-badge :listing="$listing" class="pointer-events-none absolute left-2 top-2 z-10" />
                @if($showFavorite)
                    <div class="listing-card-actions">
                        <div x-data="compareButton('{{ $listing->slug }}', '{{ route('compare.add', $listing) }}')">
                            <button
                                type="button"
                                @click.stop="add()"
                                :disabled="loading"
                                class="listing-card-action-btn"
                                title="{{ __('messages.compare_add') }}"
                            >
                                <x-icon name="compare" class="h-4 w-4" />
                            </button>
                        </div>
                        <div x-data="favoriteButton('{{ $listing->slug }}', {{ $favorited ? 'true' : 'false' }}, {{ auth()->check() ? 'true' : 'false' }}, '{{ route('login') }}')">
                            <button
                                type="button"
                                @click.stop="toggle()"
                                :disabled="loading"
                                class="listing-card-action-btn"
                                :class="favorited ? 'listing-card-action-btn-favorited' : ''"
                                :title="favorited ? '{{ __('messages.remove_from_favorites') }}' : '{{ __('messages.add_to_favorites') }}'"
                            >
                                <x-icon name="heart" variant="solid" class="h-4 w-4" x-show="favorited" x-cloak />
                                <x-icon name="heart" class="h-4 w-4" x-show="!favorited" />
                            </button>
                        </div>
                    </div>
                @endif
                @if($photoCount > 0)
                    <span class="pointer-events-none absolute bottom-2 left-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] text-white">
                        <x-icon name="image" class="mr-0.5 inline h-3 w-3" />{{ $photoCount }}
                    </span>
                @endif
            </div>

            @if($photoCount > 1)
                <div class="listing-grid-card-photos-thumbs">
                    @for($i = 0; $i < 3; $i++)
                        @php $thumb = $extraImages->values()->get($i); @endphp
                        <div class="listing-grid-card-photos-thumb relative">
                            @if($thumb)
                                <x-listing-image
                                    :image="$thumb"
                                    size="thumb"
                                    alt=""
                                    class="absolute inset-0 h-full w-full object-cover"
                                    :width="120"
                                    :height="90"
                                />
                            @endif
                        </div>
                    @endfor
                </div>
            @endif
        </a>
    </div>

    <a href="{{ route('listings.show', $listing) }}" class="flex min-h-0 flex-1 flex-col p-2.5 sm:p-3">
        <h3 class="line-clamp-2 text-sm font-semibold leading-snug group-hover:text-brand-600 sm:text-base">
            {{ $listing->vehicleName() }}
        </h3>

        @if($listing->ad_name)
            <p class="mt-0.5 line-clamp-1 text-xs text-[var(--color-text-muted)] sm:text-sm">{{ $listing->ad_name }}</p>
        @endif

        <p class="listing-grid-card-spec mt-1 text-[var(--color-text-muted)]">{{ ListingPresenter::specLine($listing) }}</p>

        @if($featureExcerpt)
            <p class="listing-grid-card-features mt-1 line-clamp-2 text-[var(--color-text-muted)]">
                {{ $featureExcerpt }}
            </p>
        @endif

        <x-listing-card-dates :listing="$listing" class="mt-1" />

        <div class="mt-auto flex items-end justify-between gap-2 pt-1.5">
            <div class="min-w-0 text-xs text-[var(--color-text-muted)]">
                @if($listing->locationLabel())
                    <span class="flex items-center gap-1">
                        <x-icon name="map-pin" class="h-3.5 w-3.5 shrink-0 text-brand-600/80" />
                        <span class="truncate">{{ $listing->locationLabel() }}</span>
                    </span>
                @endif
                @if($listing->price_negotiable && $listing->hasFixedPrice())
                    <span class="badge mt-1 bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">{{ __('messages.negotiable') }}</span>
                @endif
            </div>

            <div class="shrink-0 text-right leading-tight">
                @if($listing->price_on_request)
                    <div class="text-sm font-bold text-brand-600">{{ __('messages.price_on_request') }}</div>
                @else
                    <div class="text-base font-bold text-brand-600 sm:text-lg">{{ number_format($listing->price) }} {{ __('messages.eur') }}</div>
                    <div class="text-[11px] text-[var(--color-text-muted)]">{{ number_format($listing->priceInBgn()) }} {{ __('messages.bgn') }}</div>
                @endif
            </div>
        </div>
    </a>
</article>