@props(['listing'])

@php
    $isOwner = auth()->id() === $listing->user_id;
    $loginReturn = url()->current();
@endphp

<div class="card p-6">
    <h2 class="text-lg font-semibold">{{ __('messages.contact_seller') }}</h2>

    @if($isOwner)
        <p class="mt-3 text-sm text-[var(--color-text-muted)]">{{ __('messages.inquiry_own_listing') }}</p>
    @elseif(! auth()->check())
        <div class="relative mt-4 overflow-hidden rounded-lg border border-[var(--color-border)]">
            <div class="space-y-3 p-4 blur-sm select-none pointer-events-none" aria-hidden="true">
                <textarea rows="4" class="input" disabled placeholder="{{ __('messages.inquiry_placeholder') }}"></textarea>
                <button type="button" class="btn-primary w-full" disabled>{{ __('messages.send_message') }}</button>
            </div>
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-[var(--color-surface)]/80 p-4 text-center">
                <p class="text-sm text-[var(--color-text-muted)]">{{ __('messages.inquiry_login_required') }}</p>
                <a href="{{ route('login') }}?redirect={{ urlencode($loginReturn) }}" class="btn-primary mt-3 text-sm">
                    {{ __('messages.login_to_contact') }}
                </a>
            </div>
        </div>
    @else
        @if(session('inquiry_sent'))
            <div class="mt-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                {{ __('messages.inquiry_sent') }}
            </div>
        @endif

        <p class="mt-3 text-sm leading-relaxed text-[var(--color-text-muted)]">
            {{ __('messages.inquiry_notice', ['email' => auth()->user()->email]) }}
        </p>

        <form method="POST" action="{{ route('listings.contact', $listing) }}" class="mt-4 space-y-3">
            @csrf
            <textarea
                name="message"
                rows="5"
                required
                minlength="10"
                maxlength="2000"
                class="input @error('message') border-red-500 @enderror"
                placeholder="{{ __('messages.inquiry_placeholder') }}"
            >{{ old('message') }}</textarea>
            @error('message')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            <button type="submit" class="btn-primary w-full sm:w-auto">
                <x-icon name="send" class="mr-1 inline h-4 w-4" />{{ __('messages.send_message') }}
            </button>
        </form>
    @endif
</div>