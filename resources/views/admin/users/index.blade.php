@extends('layouts.admin')

@section('title', __('admin.nav_users'))

@section('content')
<div class="w-full">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('admin.nav_users') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.users_subtitle') }}</p>
        </div>
    </div>

    <x-admin-filters :action="route('admin.users.index')">
        <div class="min-w-[200px] flex-1">
            <label class="label">{{ __('admin.search') }}</label>
            <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('admin.search_users_placeholder') }}">
        </div>
        <div>
            <label class="label">{{ __('admin.account_type') }}</label>
            <select name="type" class="input">
                <option value="">{{ __('admin.all') }}</option>
                <option value="company" @selected(request('type') === 'company')>{{ __('messages.company_profile') }}</option>
                <option value="private" @selected(request('type') === 'private')>{{ __('messages.private_seller') }}</option>
            </select>
        </div>
    </x-admin-filters>

    <x-admin-table :headers="[__('admin.name'), __('admin.email'), __('admin.account_type'), __('admin.listings'), __('admin.joined'), '']">
        @forelse($users as $user)
            <tr class="hover:bg-[var(--color-surface-3)]">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.users.show', $user) }}" class="font-medium hover:text-brand-600">{{ $user->name }}</a>
                    @if($user->isAdmin())
                        <span class="ml-2 rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-medium text-brand-700 dark:bg-brand-950 dark:text-brand-300">Admin</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $user->email }}</td>
                <td class="px-4 py-3">{{ $user->account_type?->value }}</td>
                <td class="px-4 py-3">{{ $user->listings_count }}</td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $user->created_at?->format('d.m.Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-brand-600 hover:underline">{{ __('admin.view') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.no_results') }}</td></tr>
        @endforelse
    </x-admin-table>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection