@extends('layouts.app')

@section('title', $title . ' — ' . config('app.name'))
@section('meta_description', $sections[0]['body'] ?? $title)
@section('canonical', route('pages.about'))

@section('content')
<div class="mx-auto max-w-3xl px-4 py-8 sm:py-10">
    <h1 class="text-3xl font-bold">{{ $title }}</h1>

    <div class="mt-8 space-y-6">
        @foreach($sections as $section)
            <section class="card p-6">
                <h2 class="text-lg font-semibold">{{ $section['heading'] }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-[var(--color-text-muted)]">{{ $section['body'] }}</p>
            </section>
        @endforeach
    </div>
</div>
@endsection