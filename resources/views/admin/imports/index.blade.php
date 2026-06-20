@extends('layouts.admin')

@section('title', __('admin.nav_imports'))

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('admin.nav_imports') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.imports_subtitle') }}</p>
    </div>

    <x-admin-filters :action="route('admin.imports.index')">
        <div>
            <label class="label">{{ __('admin.status') }}</label>
            <select name="status" class="input">
                <option value="">{{ __('admin.all') }}</option>
                @foreach(['pending', 'running', 'completed', 'failed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
    </x-admin-filters>

    <x-admin-table :headers="[__('admin.company'), __('admin.status'), __('admin.found'), __('admin.created'), __('admin.updated'), __('admin.failed'), __('admin.started')]">
        @forelse($imports as $import)
            <tr class="hover:bg-[var(--color-surface-3)]">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.companies.show', $import->company) }}" class="font-medium hover:text-brand-600">{{ $import->company?->name }}</a>
                </td>
                <td class="px-4 py-3">
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-medium',
                        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' => $import->status === 'completed',
                        'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200' => $import->status === 'failed',
                        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => in_array($import->status, ['pending', 'running']),
                    ])>{{ $import->status }}</span>
                </td>
                <td class="px-4 py-3">{{ $import->total_found ?? '—' }}</td>
                <td class="px-4 py-3">{{ $import->created_count }}</td>
                <td class="px-4 py-3">{{ $import->updated_count }}</td>
                <td class="px-4 py-3">{{ $import->failed_count }}</td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $import->started_at?->format('d.m.Y H:i') ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.no_results') }}</td></tr>
        @endforelse
    </x-admin-table>

    <div class="mt-4">{{ $imports->links() }}</div>
</div>
@endsection