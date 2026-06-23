@extends('layouts.app')

@section('title', $title . ' — ' . config('app.name'))
@section('meta_description', $intro)
@section('canonical', route('pages.contact'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:py-10">
    <h1 class="text-3xl font-bold">{{ $title }}</h1>
    <p class="mt-4 text-sm leading-relaxed text-[var(--color-text-muted)]">{{ $intro }}</p>

    <section class="card mt-8 p-6">
        <h2 class="text-lg font-semibold">{{ __('pages.contact.form_title') }}</h2>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('pages.contact.form_subtitle') }}</p>

        <form method="POST" action="{{ route('pages.contact.store') }}" class="mt-5 space-y-4">
            @csrf

            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="label" for="contact-name">{{ __('pages.contact.name') }}</label>
                <input type="text" name="name" id="contact-name" value="{{ old('name') }}" class="input" required maxlength="120" autocomplete="name">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="contact-email">{{ __('messages.email') }}</label>
                <input type="email" name="email" id="contact-email" value="{{ old('email', auth()->user()?->email) }}" class="input" required maxlength="255" autocomplete="email">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="contact-subject">{{ __('pages.contact.subject') }}</label>
                <input type="text" name="subject" id="contact-subject" value="{{ old('subject') }}" class="input" maxlength="200">
                @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="contact-message">{{ __('pages.contact.message') }}</label>
                <textarea name="message" id="contact-message" rows="6" class="input" required maxlength="5000">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="contact-captcha">{{ $captcha['question'] }}</label>
                <input type="number" name="captcha_answer" id="contact-captcha" value="{{ old('captcha_answer') }}" class="input max-w-[12rem]" required inputmode="numeric" autocomplete="off">
                @error('captcha_answer')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn-primary">{{ __('pages.contact.send') }}</button>
        </form>
    </section>

    @if($phone)
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold">{{ __('messages.phone') }}</h2>
            <a href="tel:{{ $phone }}" class="mt-3 block font-medium">{{ $phone }}</a>
        </div>
    @endif

    <section class="card mt-6 p-6">
        <h2 class="text-lg font-semibold">{{ __('pages.contact.social_title') }}</h2>
        <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('pages.contact.social_body') }}</p>
        <div class="mt-5 flex flex-wrap gap-3">
            @foreach(config('seo.social') as $network => $social)
                @if($social['url'] ?? null)
                    <a href="{{ $social['url'] }}" rel="noopener noreferrer" target="_blank"
                       class="inline-flex items-center gap-2 rounded-lg border border-[var(--color-border)] px-4 py-2.5 text-sm transition hover:border-brand-500 hover:text-brand-600">
                        <x-icon :name="$social['icon']" class="h-4 w-4" />{{ $social['label'] ?? $network }}
                    </a>
                @endif
            @endforeach
        </div>
    </section>
</div>
@endsection