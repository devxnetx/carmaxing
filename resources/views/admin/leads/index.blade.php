@extends('layouts.admin')

@section('title', __('admin.nav_leads'))

@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('admin.nav_leads') }}</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.leads_subtitle') }}</p>
    </div>

    <div class="card mb-6 p-5">
        <h2 class="font-semibold">{{ __('admin.extract_leads') }}</h2>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('admin.extract_leads_help') }}</p>

        @if($activeRun)
            <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                <div class="font-medium">{{ __('admin.lead_extraction_in_progress') }}</div>
                <div class="mt-1 text-xs">
                    {{ $activeRun->city_label ?: $activeRun->source_url }}
                    · {{ __('admin.status') }}: {{ $activeRun->status }}
                    @if($activeRun->total_pages)
                        · {{ __('admin.page') }} {{ $activeRun->current_page }}/{{ $activeRun->total_pages }}
                    @endif
                    @if($activeRun->total_found)
                        · {{ $activeRun->processed_count }}/{{ $activeRun->total_found }} {{ __('admin.processed') }}
                    @endif
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.leads.extract') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="label" for="source_url">{{ __('admin.dealers_directory_url') }}</label>
                <input
                    type="url"
                    name="source_url"
                    id="source_url"
                    class="input"
                    placeholder="https://www.mobile.bg/dealers/location-grad-stara-zagora"
                    value="{{ old('source_url') }}"
                    required
                    @disabled($activeRun)
                >
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary text-sm" @disabled($activeRun)>{{ __('admin.start_extraction') }}</button>
                <form method="POST" action="{{ route('admin.leads.refresh-counts') }}">
                    @csrf
                    @if(request('city'))
                        <input type="hidden" name="city" value="{{ request('city') }}">
                    @endif
                    <button type="submit" class="btn-secondary text-sm">{{ __('admin.refresh_car_counts') }}</button>
                </form>
            </div>
        </form>

        @if($recentRuns->isNotEmpty())
            <div class="mt-5 border-t border-[var(--color-border)] pt-4">
                <h3 class="text-sm font-medium">{{ __('admin.recent_extractions') }}</h3>
                <div class="mt-2 divide-y divide-[var(--color-border)] text-sm">
                    @foreach($recentRuns as $run)
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2 first:pt-2">
                            <div class="min-w-0">
                                <div class="truncate font-medium">{{ $run->city_label ?: $run->source_url }}</div>
                                <div class="text-xs text-[var(--color-text-muted)]">
                                    {{ $run->created_count }} {{ __('admin.created') }},
                                    {{ $run->updated_count }} {{ __('admin.updated') }},
                                    {{ $run->failed_count }} {{ __('admin.failed') }}
                                </div>
                            </div>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' => $run->status === 'completed',
                                'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200' => $run->status === 'failed',
                                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => in_array($run->status, ['pending', 'running']),
                            ])>{{ $run->status }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <x-admin-filters :action="route('admin.leads.index')">
        <div class="min-w-[180px]">
            <label class="label">{{ __('admin.city') }}</label>
            <select name="city" class="input">
                <option value="">{{ __('admin.all') }}</option>
                @foreach($cities as $city)
                    <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">{{ __('admin.contacted') }}</label>
            <select name="contacted" class="input">
                <option value="">{{ __('admin.all') }}</option>
                <option value="pending_invite" @selected(request('contacted') === 'pending_invite')>{{ __('admin.contacted_pending_invite') }}</option>
                <option value="email_sent" @selected(request('contacted') === 'email_sent')>{{ __('admin.contacted_email_sent') }}</option>
            </select>
        </div>
        <div>
            <label class="label">{{ __('admin.onboarded') }}</label>
            <select name="onboarded" class="input">
                <option value="">{{ __('admin.all') }}</option>
                <option value="1" @selected(request('onboarded') === '1')>{{ __('admin.onboarded_yes') }}</option>
                <option value="0" @selected(request('onboarded') === '0')>{{ __('admin.onboarded_no') }}</option>
            </select>
        </div>
        <div class="min-w-[200px] flex-1">
            <label class="label">{{ __('admin.search') }}</label>
            <input type="search" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('admin.search_leads_placeholder') }}">
        </div>
        <div>
            <label class="label">{{ __('admin.sort') }}</label>
            <select name="sort" class="input">
                <option value="cars_desc" @selected($sort === 'cars_desc')>{{ __('admin.sort_cars_desc') }}</option>
                <option value="cars_asc" @selected($sort === 'cars_asc')>{{ __('admin.sort_cars_asc') }}</option>
                <option value="name_asc" @selected($sort === 'name_asc')>{{ __('admin.sort_name_asc') }}</option>
                <option value="name_desc" @selected($sort === 'name_desc')>{{ __('admin.sort_name_desc') }}</option>
                <option value="newest" @selected($sort === 'newest')>{{ __('admin.sort_newest') }}</option>
                <option value="oldest" @selected($sort === 'oldest')>{{ __('admin.sort_oldest') }}</option>
            </select>
        </div>
    </x-admin-filters>

    <x-admin-table
        :headers="[__('admin.company'), __('admin.city'), __('admin.cars'), __('messages.phone'), __('admin.email'), __('admin.contacted'), __('admin.onboarded')]"
        :column-classes="['w-[4.5rem] max-w-[4.5rem]', null, 'w-16', null, null, null, null]"
    >
        @forelse($leads as $lead)
            <tr class="hover:bg-[var(--color-surface-3)]">
                <td class="max-w-[4.5rem] px-4 py-3">
                    <div class="truncate font-medium" title="{{ $lead->name }}">{{ $lead->name }}</div>
                    <a href="{{ $lead->mobile_bg_url }}" target="_blank" class="block truncate text-xs text-brand-600 hover:underline" title="{{ $lead->mobile_bg_url }}">{{ $lead->mobile_bg_url }}</a>
                </td>
                <td class="px-4 py-3 text-[var(--color-text-muted)]">
                    <div>{{ $lead->city ?: '—' }}</div>
                    @if($lead->source_city)
                        <div class="text-xs">{{ $lead->source_city }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 font-medium">{{ number_format($lead->listings_count) }}</td>
                <td class="px-4 py-3">{{ $lead->phone ?: '—' }}</td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="relative max-w-[10rem]">
                        @csrf
                        @method('PUT')
                        <input
                            type="email"
                            name="email"
                            class="input w-full min-w-[8rem] py-1.5 pr-8 text-xs"
                            placeholder="{{ __('admin.lead_email_placeholder') }}"
                            value="{{ old('email', $lead->email) }}"
                        >
                        <button
                            type="submit"
                            class="absolute inset-y-0 right-0 flex items-center px-2 text-[var(--color-text-muted)] hover:text-brand-600"
                            aria-label="{{ __('messages.save') }}"
                        >
                            <x-icon name="save" class="h-4 w-4" />
                        </button>
                    </form>
                </td>
                <td class="px-4 py-3">
                    @if($lead->contacted_status->value === 'email_sent')
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                            {{ __('admin.contacted_email_sent') }}
                        </span>
                    @elseif($lead->isOnboarded())
                        <span class="text-xs text-[var(--color-text-muted)]">—</span>
                    @elseif($lead->email)
                        <form method="POST" action="{{ route('admin.leads.send-invite', $lead) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-primary px-3 py-1.5 text-xs">{{ __('admin.send_invite') }}</button>
                        </form>
                    @else
                        <span class="text-xs text-[var(--color-text-muted)]">{{ __('admin.add_email_to_invite') }}</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($lead->isOnboarded())
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-950 dark:text-green-200">
                            {{ __('admin.onboarded_yes') }}
                        </span>
                        @if($lead->company)
                            <a href="{{ route('admin.companies.show', $lead->company) }}" class="mt-1 block text-xs text-brand-600 hover:underline">{{ __('admin.view') }}</a>
                        @endif
                    @else
                        <span class="rounded-full bg-[var(--color-surface-3)] px-2 py-0.5 text-xs font-medium text-[var(--color-text-muted)]">
                            {{ __('admin.onboarded_no') }}
                        </span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-[var(--color-text-muted)]">{{ __('admin.no_results') }}</td></tr>
        @endforelse
    </x-admin-table>

    <div class="mt-4">{{ $leads->links() }}</div>
</div>
@endsection