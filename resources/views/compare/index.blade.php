@extends('layouts.app')

@section('title', __('messages.compare'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:py-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('messages.compare') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('messages.compare_subtitle') }}</p>
        </div>
        @if($listings->isNotEmpty())
            <button type="button" x-data @click="fetch('{{ route('compare.clear') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } }).then(() => location.reload())" class="btn-secondary text-sm">
                {{ __('messages.compare_clear') }}
            </button>
        @endif
    </div>

    @if($listings->isEmpty())
        <div class="card mt-6 p-12 text-center text-[var(--color-text-muted)]">
            <p>{{ __('messages.compare_empty') }}</p>
            <a href="{{ route('search') }}" class="btn-primary mt-4 inline-flex">{{ __('messages.search') }}</a>
        </div>
    @else
        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-[var(--color-surface)] p-3 text-left font-medium text-[var(--color-text-muted)]">{{ __('messages.specifications') }}</th>
                        @foreach($listings as $listing)
                            <th class="min-w-[11rem] border-l border-[var(--color-border)] p-3 text-left align-top">
                                <a href="{{ route('listings.show', $listing) }}" class="font-semibold hover:text-brand-600">{{ $listing->vehicleName() }}</a>
                                @if($listing->ad_name)
                                    <div class="mt-0.5 line-clamp-2 text-xs text-[var(--color-text-muted)]">{{ $listing->ad_name }}</div>
                                @endif
                                @if($listing->images->first())
                                    <x-listing-image :image="$listing->images->first()" size="medium" alt="" class="mt-2 h-24 w-full rounded-lg object-cover" :width="160" :height="120" />
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @foreach($specSections as $section)
                        <tr class="bg-[var(--color-surface-3)]">
                            <td colspan="{{ $listings->count() + 1 }}" class="sticky left-0 p-3 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">
                                {{ $section['label'] }}
                            </td>
                        </tr>
                        @foreach($section['rows'] as $row)
                            <tr>
                                <td class="sticky left-0 z-10 bg-[var(--color-surface)] p-3 font-medium text-[var(--color-text-muted)]">{{ $row['label'] }}</td>
                                @foreach($listings as $listing)
                                    @php $value = $row['value']($listing); @endphp
                                    <td class="border-l border-[var(--color-border)] p-3 align-top {{ $value ? '' : 'text-[var(--color-text-muted)]' }}">
                                        {{ $value ?: '—' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach

                    @if($featureSections !== [])
                        <tr class="bg-[var(--color-surface-3)]">
                            <td colspan="{{ $listings->count() + 1 }}" class="sticky left-0 p-3 text-xs font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">
                                {{ __('messages.features') }}
                            </td>
                        </tr>
                        @foreach($featureSections as $category)
                            <tr class="bg-[var(--color-surface)]">
                                <td colspan="{{ $listings->count() + 1 }}" class="sticky left-0 p-3 text-sm font-semibold">
                                    {{ $category['name'] }}
                                </td>
                            </tr>
                            @foreach($category['features'] as $feature)
                                <tr>
                                    <td class="sticky left-0 z-10 bg-[var(--color-surface)] p-3 text-[var(--color-text-muted)]">{{ $feature['name'] }}</td>
                                    @foreach($listings as $listing)
                                        <td class="border-l border-[var(--color-border)] p-3 text-center">
                                            @if($feature['presence'][$listing->id] ?? false)
                                                <x-icon name="check" class="mx-auto h-5 w-5 text-brand-600" />
                                            @else
                                                <span class="text-[var(--color-text-muted)]">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection