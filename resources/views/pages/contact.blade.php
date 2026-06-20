@extends('layouts.app')

@section('title', $title . ' — ' . config('app.name'))
@section('meta_description', $intro)
@section('canonical', route('pages.contact'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:py-10">
    <h1 class="text-3xl font-bold">{{ $title }}</h1>
    <p class="mt-4 text-sm leading-relaxed text-[var(--color-text-muted)]">{{ $intro }}</p>

    <div class="mt-8 grid gap-6 sm:grid-cols-2">
        <div class="card p-6">
            <h2 class="text-lg font-semibold">{{ __('pages.contact.email_title') }}</h2>
            <a href="mailto:{{ $email }}" class="mt-3 block text-brand-600 hover:underline">{{ $email }}</a>
        </div>
        @if($phone)
            <div class="card p-6">
                <h2 class="text-lg font-semibold">{{ __('messages.phone') }}</h2>
                <a href="tel:{{ $phone }}" class="mt-3 block font-medium">{{ $phone }}</a>
            </div>
        @endif
    </div>

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