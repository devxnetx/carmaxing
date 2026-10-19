@props([
    'name',
    'value' => null,
])

@php
    $initial = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (is_string($value) ? substr($value, 0, 10) : '');

    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $initial)) {
        $initial = '';
    }
@endphp

<div class="relative" x-data="datePicker(@js($initial), @js(app()->getLocale()))">
    <input
        x-ref="display"
        type="text"
        class="input pr-10"
        placeholder="{{ __('messages.date_placeholder') }}"
        autocomplete="off"
        inputmode="numeric"
        @keydown.enter.prevent="commitTyped()"
    >
    <button
        type="button"
        class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-[var(--color-text-muted)]"
        @click="hasValue ? clear() : open()"
        :aria-label="hasValue ? @js(__('messages.date_clear')) : @js(__('messages.date_open'))"
    >
        <svg x-show="!hasValue" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5a.75.75 0 0 0-.75.75v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5a.75.75 0 0 0-.75-.75H4.75Z" clip-rule="evenodd" />
        </svg>
        <span x-show="hasValue" x-cloak class="text-lg leading-none">&times;</span>
    </button>
    <input type="hidden" x-ref="value" name="{{ $name }}" value="{{ $initial }}">
</div>
