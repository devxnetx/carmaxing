@extends('layouts.app')

@section('title', $listing->exists ? __('messages.edit') : __('messages.new_listing'))

@section('content')
<div class="mx-auto max-w-4xl px-4 py-6 sm:py-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ $listing->exists ? __('messages.edit') : __('messages.new_listing') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.listing_form_help') }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn-secondary text-sm">{{ __('messages.my_listings') }}</a>
    </div>

    @include('listings._form-body')
</div>
@endsection