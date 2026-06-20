@extends('layouts.app')

@section('title', __('tenders.title'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold">{{ __('tenders.title') }}</h1>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                    {{ __('tenders.experimental') }}
                </span>
            </div>
            <p class="mt-1 max-w-2xl text-sm text-[var(--color-text-muted)]">{{ __('tenders.subtitle') }}</p>
        </div>
        @auth
            <a href="{{ route('my.tenders.create') }}" class="btn-primary">{{ __('tenders.start_tender') }}</a>
        @endauth
    </div>

    @if($tenders->isEmpty())
        <div class="card mt-8 p-8 text-center text-[var(--color-text-muted)]">
            {{ __('tenders.empty') }}
        </div>
    @else
        <div class="tender-cards-grid mt-8">
            @foreach($tenders as $tender)
                <x-tender-card :tender="$tender" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $tenders->links() }}
        </div>
    @endif
</div>
@endsection