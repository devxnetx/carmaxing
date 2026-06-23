@extends('layouts.app')

@section('title', __('messages.search_form_title'))
@section('meta_description', __('messages.search_form_description'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('messages.search_form_heading') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.search_form_subtitle') }}</p>
    </div>

    <x-search-form
        :scope="$scope"
        :brands="$brands"
        :brand-counts="$brandCounts"
        :regions="$regions"
        :feature-categories="$featureCategories"
        :countries="$countries"
        :filters="$filters"
        :extended-open="true"
        :show-scope-tabs="true"
        :show-extended-toggle="false"
        scope-tab-target="form"
    />
</div>
@endsection