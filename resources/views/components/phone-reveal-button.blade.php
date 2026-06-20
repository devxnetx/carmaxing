@props([
    'phone',
    'phoneClickUrl' => null,
])

@php
    use App\Support\PhoneNumber;

    $tel = $phone;
    $display = PhoneNumber::formatForDisplay($phone);
    $masked = PhoneNumber::maskForDisplay($phone);
@endphp

<button
    type="button"
    x-data="phoneReveal({
        tel: @js($tel),
        display: @js($display),
        masked: @js($masked),
        trackUrl: @js($phoneClickUrl),
        revealHint: @js(__('messages.phone_reveal_hint')),
        callHint: @js(__('messages.phone_call_hint')),
    })"
    @click="handleClick()"
    :aria-label="revealed ? callHint : revealHint"
    :title="revealed ? callHint : revealHint"
    {{ $attributes->class(['inline-flex items-center justify-center']) }}
>
    <x-icon name="phone" class="mr-1 inline h-4 w-4 shrink-0" />
    <span x-text="label"></span>
</button>