@extends('layouts.app')

@section('title', __('tenders.my_tenders'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('tenders.my_tenders') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('tenders.create_subtitle') }}</p>
        </div>
        <a href="{{ route('my.tenders.create') }}" class="btn-primary">{{ __('tenders.start_tender') }}</a>
    </div>

    @if($tenders->isEmpty())
        <div class="card mt-8 p-8 text-center text-[var(--color-text-muted)]">
            {{ __('tenders.empty') }}
        </div>
    @else
        <div class="mt-8 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)] text-xs uppercase tracking-wide text-[var(--color-text-muted)]">
                        <th class="px-3 py-2">{{ __('tenders.vehicle_details') }}</th>
                        <th class="px-3 py-2">{{ __('tenders.status') }}</th>
                        <th class="px-3 py-2">{{ __('tenders.current_high_bid') }}</th>
                        <th class="px-3 py-2">{{ __('tenders.ends_in') }}</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenders as $tender)
                        <tr class="border-b border-[var(--color-border)]">
                            <td class="px-3 py-3">
                                <div class="font-medium">{{ $tender->vehicleName() }}</div>
                                <div class="text-xs text-[var(--color-text-muted)]">{{ $tender->reference_number }}</div>
                            </td>
                            <td class="px-3 py-3">
                                @if($tender->status === \App\Enums\TenderStatus::Active)
                                    {{ __('tenders.ends_in') }}
                                @elseif($tender->status === \App\Enums\TenderStatus::Ended)
                                    {{ __('tenders.ended') }}
                                @elseif($tender->status === \App\Enums\TenderStatus::Awarded)
                                    {{ __('tenders.awarded') }}
                                @else
                                    {{ $tender->status->value }}
                                @endif
                            </td>
                            <td class="px-3 py-3 font-medium">
                                {{ $tender->current_high_bid_amount ? number_format($tender->current_high_bid_amount).' €' : '—' }}
                            </td>
                            <td class="px-3 py-3 text-[var(--color-text-muted)]">
                                {{ $tender->status === \App\Enums\TenderStatus::Active ? $tender->ends_at->diffForHumans() : $tender->ends_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('my.tenders.manage', $tender) }}" class="text-brand-600 hover:underline">{{ __('admin.manage') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $tenders->links() }}</div>
    @endif
</div>
@endsection