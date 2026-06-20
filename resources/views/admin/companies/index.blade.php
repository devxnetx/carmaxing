@extends('layouts.admin')

@section('title', __('admin.nav_companies'))

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('admin.nav_companies') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.companies_subtitle') }}</p>
    </div>

    <x-admin-filters :action="route('admin.companies.index')">
        <div class="min-w-[200px] flex-1">
            <label class="label">{{ __('admin.search') }}</label>
            <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('admin.search_companies_placeholder') }}">
        </div>
        <div>
            <label class="label">{{ __('admin.verification') }}</label>
            <select name="verified" class="input">
                <option value="">{{ __('admin.all') }}</option>
                <option value="1" @selected(request('verified') === '1')>{{ __('messages.verified_dealer') }}</option>
                <option value="0" @selected(request('verified') === '0')>{{ __('admin.not_verified') }}</option>
            </select>
        </div>
    </x-admin-filters>

    <x-admin-table
        class="w-full"
        :headers="[__('admin.company'), __('admin.owner'), __('admin.listings'), __('admin.verification'), '']"
        :column-classes="['w-[4.5rem] max-w-[4.5rem]', null, null, null, 'w-24']"
    >
        @forelse($companies as $company)
            <tr class="hover:bg-[var(--color-surface-3)]">
                <td class="max-w-[4.5rem] px-4 py-3">
                    <a href="{{ route('admin.companies.show', $company) }}" class="block truncate font-medium hover:text-brand-600" title="{{ $company->name }}">{{ $company->name }}</a>
                    <div class="truncate text-xs text-[var(--color-text-muted)]" title="{{ $company->city }}">{{ $company->city }}</div>
                </td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $company->user?->email }}</td>
                <td class="px-4 py-3">{{ $company->listings_count }}</td>
                <td class="px-4 py-3">
                    @if($company->isVerifiedDealer())
                        <x-verified-badge :company="$company" />
                    @else
                        <span class="text-xs text-[var(--color-text-muted)]">{{ __('admin.not_verified') }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.companies.show', $company) }}" class="text-sm text-brand-600 hover:underline">{{ __('admin.manage') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.no_results') }}</td></tr>
        @endforelse
    </x-admin-table>

    <div class="mt-4">{{ $companies->links() }}</div>
</div>
@endsection