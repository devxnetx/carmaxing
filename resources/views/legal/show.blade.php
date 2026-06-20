@extends('layouts.app')

@section('title', $title . ' — ' . config('app.name'))
@section('meta_description', $sections[0]['body'] ?? $title)
@section('canonical', $page === 'privacy' ? route('legal.privacy') : route('legal.terms'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <h1 class="text-3xl font-bold">{{ $title }}</h1>
    <p class="mt-2 text-sm text-[var(--color-text-muted)]">{{ __('legal.last_updated') }}: {{ $updated }}</p>

    <div class="mt-8 space-y-8">
        @foreach($sections as $section)
            <section class="card p-6">
                <h2 class="text-lg font-semibold">{{ $section['heading'] }}</h2>
                <div class="mt-3 space-y-3 text-sm leading-relaxed text-[var(--color-text-muted)]">
                    @foreach($section['paragraphs'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection