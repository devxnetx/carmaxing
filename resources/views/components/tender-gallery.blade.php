@props(['tender'])

@php
    $imageUrls = $tender->images->map(fn ($img) => [
        'large' => $img->url('large'),
        'medium' => $img->url('medium'),
        'thumb' => $img->url('thumb'),
    ])->values();
@endphp

<div
    class="card overflow-hidden"
    x-data="listingGallery(@js($imageUrls))"
    @keydown.escape.window="closeLightbox()"
    @keydown.arrow-left.window="lightboxOpen && prev()"
    @keydown.arrow-right.window="lightboxOpen && next()"
>
    <div
        class="relative aspect-[16/10] bg-[var(--color-surface-3)]"
        @touchstart="onTouchStart($event)"
        @touchend="onTouchEnd($event)"
    >
        <template x-if="count">
            <img
                :src="images[active].large"
                :srcset="`${images[active].thumb} 320w, ${images[active].medium} 800w, ${images[active].large} 1600w`"
                sizes="(max-width: 1024px) 100vw, 66vw"
                alt="{{ $tender->vehicleName() }}"
                width="800"
                height="500"
                fetchpriority="high"
                decoding="async"
                class="h-full w-full cursor-zoom-in object-cover"
                @click="openLightbox()"
            >
        </template>
        <template x-if="!count">
            <div class="flex h-full items-center justify-center text-[var(--color-text-muted)]">{{ __('messages.no_photo') }}</div>
        </template>

        <template x-if="count > 1">
            <div class="pointer-events-none absolute inset-0 z-10">
                <div class="flex h-full items-center justify-between px-3">
                    <button
                        type="button"
                        @click.stop="prev()"
                        class="pointer-events-auto flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70"
                        aria-label="{{ __('messages.gallery_prev') }}"
                    >
                        <x-icon name="chevron-left" class="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        @click.stop="next()"
                        class="pointer-events-auto flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70"
                        aria-label="{{ __('messages.gallery_next') }}"
                    >
                        <x-icon name="chevron-right" class="h-5 w-5" />
                    </button>
                </div>
                <span
                    class="absolute bottom-3 right-3 rounded-md bg-black/60 px-2.5 py-1 text-xs font-medium text-white"
                    x-text="`${active + 1} / ${count}`"
                ></span>
            </div>
        </template>
    </div>

    <template x-if="count > 1">
        <div class="flex gap-1.5 overflow-x-auto border-t border-[var(--color-border)] p-2">
            <template x-for="(url, index) in images" :key="index">
                <button
                    type="button"
                    @click="select(index)"
                    @dblclick="openLightbox(index)"
                    class="relative shrink-0 overflow-hidden rounded-md"
                    :class="active === index ? 'ring-2 ring-brand-600' : 'opacity-80 hover:opacity-100'"
                >
                    <img :src="url.thumb" alt="" width="96" height="72" loading="lazy" decoding="async" class="h-16 w-24 object-cover sm:h-[4.5rem] sm:w-28">
                </button>
            </template>
        </div>
    </template>

    <template x-teleport="body">
        <div
            x-show="lightboxOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[100] flex flex-col bg-black/95"
            @click.self="closeLightbox()"
        >
            <div class="flex items-center justify-between px-4 py-3 text-white">
                <span class="text-sm" x-text="`${active + 1} / ${count}`"></span>
                <button type="button" @click="closeLightbox()" class="flex h-10 w-10 items-center justify-center rounded-full hover:bg-white/10" aria-label="{{ __('messages.gallery_close') }}">
                    <x-icon name="x" class="h-6 w-6" />
                </button>
            </div>

            <div class="relative flex flex-1 items-center justify-center px-4 pb-4" @touchstart="onTouchStart($event)" @touchend="onTouchEnd($event)">
                <img :src="images[active].large" alt="" class="max-h-[75vh] max-w-full object-contain">

                <template x-if="count > 1">
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-between px-4">
                        <button type="button" @click.stop="prev()" class="pointer-events-auto flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20">
                            <x-icon name="chevron-left" class="h-5 w-5" />
                        </button>
                        <button type="button" @click.stop="next()" class="pointer-events-auto flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20">
                            <x-icon name="chevron-right" class="h-5 w-5" />
                        </button>
                    </div>
                </template>
            </div>

            <template x-if="count > 1">
                <div class="flex shrink-0 gap-2 overflow-x-auto px-4 pb-4">
                    <template x-for="(url, index) in images" :key="'lb-' + index">
                        <button type="button" @click="select(index)" class="shrink-0 overflow-hidden rounded" :class="active === index ? 'ring-2 ring-white' : 'opacity-50 hover:opacity-90'">
                            <img :src="url.thumb" alt="" class="h-14 w-20 object-cover sm:h-16 sm:w-24">
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </template>
</div>