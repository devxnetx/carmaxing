@props([
    'name' => 'phone',
    'value' => null,
    'required' => false,
    'showHint' => true,
])

@php
    use App\Support\PhoneNumber;

    $localValue = PhoneNumber::localPart(old($name, $value));
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    <div class="flex">
        <span class="inline-flex shrink-0 items-center rounded-l-lg border border-r-0 border-[var(--color-border)] bg-[var(--color-surface-3)] px-3 text-sm font-medium text-[var(--color-text-muted)]">
            +359
        </span>
        <input
            type="tel"
            name="{{ $name }}"
            value="{{ $localValue }}"
            class="input min-w-0 flex-1 rounded-l-none @error($name) border-red-500 @enderror"
            inputmode="numeric"
            autocomplete="tel-national"
            maxlength="9"
            minlength="9"
            pattern="[0-9]{9}"
            placeholder="888123456"
            @required($required)
        >
    </div>
    @if($showHint)
        <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ __('messages.phone_format_hint') }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>