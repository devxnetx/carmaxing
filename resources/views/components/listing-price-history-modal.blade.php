@props(['listing', 'open' => 'priceHistoryOpen'])

<div
    x-show="{{ $open }}"
    x-cloak
    @keydown.escape.window="{{ $open }} = false"
    class="fixed inset-0 z-[60] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('messages.price_history_title') }}"
>
    <div class="absolute inset-0 bg-black/50" @click="{{ $open }} = false"></div>

    <div class="relative w-full max-w-md rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] p-6 shadow-2xl">
        <button
            type="button"
            @click="{{ $open }} = false"
            class="absolute right-4 top-4 rounded-lg p-1 text-[var(--color-text-muted)] transition hover:bg-[var(--color-surface-3)] hover:text-[var(--color-text)]"
            aria-label="{{ __('messages.close') }}"
        >
            <x-icon name="x" class="h-5 w-5" />
        </button>

        <h2 class="pr-8 text-lg font-semibold">{{ __('messages.price_history_title') }}</h2>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ $listing->composeDisplayTitle() }}</p>

        <ul class="mt-5 max-h-80 space-y-3 overflow-y-auto">
            @foreach($listing->priceChanges as $change)
                <li class="flex items-center justify-between gap-3 rounded-xl border border-[var(--color-border)] px-4 py-3 text-sm">
                    <div>
                        <div class="font-medium text-[var(--color-text)]">
                            {{ number_format($change->old_price) }} €
                            <span class="text-[var(--color-text-muted)]">→</span>
                            {{ number_format($change->new_price) }} €
                        </div>
                        @if($change->created_at)
                            <div class="mt-1 text-xs text-[var(--color-text-muted)]">
                                {{ $change->created_at->format('d.m.Y H:i') }}
                            </div>
                        @endif
                    </div>
                    @if($change->new_price < $change->old_price)
                        <x-icon name="arrow-down" class="h-5 w-5 shrink-0 text-green-600" title="{{ __('messages.price_decreased') }}" />
                    @elseif($change->new_price > $change->old_price)
                        <x-icon name="arrow-up" class="h-5 w-5 shrink-0 text-red-600" title="{{ __('messages.price_increased') }}" />
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>