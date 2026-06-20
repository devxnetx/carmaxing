@extends('layouts.admin')

@section('title', __('admin.nav_settings'))

@section('content')
<div class="w-full max-w-2xl">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">{{ __('admin.settings_heading') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.settings_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card space-y-6 p-6">
        @csrf
        @method('PUT')

        <div>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ __('admin.tenders_feature') }}</h2>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.tenders_feature_help') }}</p>
                </div>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input type="hidden" name="tenders_enabled" value="0">
                    <input
                        type="checkbox"
                        name="tenders_enabled"
                        value="1"
                        class="peer sr-only"
                        @checked($tendersEnabled)
                    >
                    <span class="h-6 w-11 rounded-full bg-[var(--color-surface-3)] after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-full"></span>
                </label>
            </div>
            <p class="mt-3 text-xs text-[var(--color-text-muted)]">
                {{ $tendersEnabled ? __('admin.tenders_enabled') : __('tenders.feature_disabled') }}
            </p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">{{ __('messages.save') }}</button>
        </div>
    </form>
</div>
@endsection