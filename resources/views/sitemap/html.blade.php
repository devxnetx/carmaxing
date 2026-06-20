@extends('layouts.app')

@section('title', __('messages.sitemap'))
@section('meta_description', __('messages.sitemap_meta'))
@section('canonical', route('sitemap.page'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:py-10">
    <h1 class="text-2xl font-bold">{{ __('messages.sitemap') }}</h1>
    <p class="mt-3 text-sm leading-relaxed text-[var(--color-text-muted)]">{{ __('messages.sitemap_intro') }}</p>

    <p class="mt-4 text-sm">
        <a href="{{ $xmlUrl }}" class="font-medium text-brand-600 hover:underline">{{ __('messages.sitemap_xml_link') }}</a>
        <span class="text-[var(--color-text-muted)]"> — {{ __('messages.sitemap_xml_hint') }}</span>
    </p>

    <div class="mt-8 space-y-10">
        @foreach($sections as $section)
            @if(!empty($section['links']))
                <section>
                    <h2 class="text-lg font-semibold">{{ $section['title'] }}</h2>
                    @if(!empty($section['note']))
                        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ $section['note'] }}</p>
                    @endif
                    <ul class="mt-4 columns-1 gap-x-8 text-sm sm:columns-2">
                        @foreach($section['links'] as $link)
                            <li class="mb-2 break-inside-avoid">
                                <a href="{{ $link['url'] }}" class="text-brand-600 transition hover:underline">{{ $link['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach
    </div>
</div>
@endsection