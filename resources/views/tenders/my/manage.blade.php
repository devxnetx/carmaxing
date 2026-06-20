@extends('layouts.app')

@section('title', __('tenders.manage_heading'))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('tenders.manage_heading') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">
                {{ __('tenders.reference', ['ref' => $tender->reference_number]) }} · {{ $tender->vehicleName() }}
            </p>
        </div>
        <a href="{{ route('tenders.show', $tender) }}" class="btn-secondary">{{ __('tenders.view_public') }}</a>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-4">
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('tenders.status') }}</div>
            <div class="mt-1 font-semibold">
                @if($tender->status === \App\Enums\TenderStatus::Active)
                    {{ __('tenders.ends_in') }} {{ $tender->ends_at->diffForHumans() }}
                @elseif($tender->status === \App\Enums\TenderStatus::Ended)
                    {{ __('tenders.ended') }}
                @elseif($tender->status === \App\Enums\TenderStatus::Awarded)
                    {{ __('tenders.awarded') }}
                @else
                    {{ $tender->status->value }}
                @endif
            </div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('tenders.current_high_bid') }}</div>
            <div class="mt-1 text-xl font-bold text-brand-600">
                {{ $tender->current_high_bid_amount ? number_format($tender->current_high_bid_amount).' €' : __('tenders.no_bids_yet') }}
            </div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ trans_choice('tenders.bid_count', $tender->bid_count) }}</div>
            <div class="mt-1 text-xl font-bold">{{ $tender->bid_count }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs text-[var(--color-text-muted)]">{{ __('tenders.minimum_price') }}</div>
            <div class="mt-1 font-semibold">{{ $tender->minimum_price ? number_format($tender->minimum_price).' €' : '—' }}</div>
        </div>
    </div>

    <div class="card mt-8 overflow-x-auto">
        <div class="border-b border-[var(--color-border)] px-5 py-4">
            <h2 class="font-semibold">{{ __('tenders.bidders') }}</h2>
            <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ __('tenders.seller_hidden') }} — {{ __('tenders.bidders') }} {{ __('admin.name') }}, {{ __('admin.email') }}</p>
        </div>

        @if($bids->isEmpty())
            <p class="p-5 text-sm text-[var(--color-text-muted)]">{{ __('tenders.no_bidders') }}</p>
        @else
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)] text-xs uppercase tracking-wide text-[var(--color-text-muted)]">
                        <th class="px-5 py-3">{{ __('tenders.bidder') }}</th>
                        <th class="px-5 py-3">{{ __('admin.email') }}</th>
                        <th class="px-5 py-3">{{ __('tenders.amount') }}</th>
                        <th class="px-5 py-3">{{ __('tenders.status') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bids as $bid)
                        <tr class="border-b border-[var(--color-border)]">
                            <td class="px-5 py-3">
                                <div class="font-medium">{{ $bid->user->name }}</div>
                                @if($bid->user->phone)
                                    <div class="text-xs text-[var(--color-text-muted)]">{{ $bid->user->phone }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3">{{ $bid->user->email }}</td>
                            <td class="px-5 py-3 font-semibold">{{ number_format($bid->amount) }} €</td>
                            <td class="px-5 py-3">{{ __('tenders.bid_status_'.$bid->status->value) }}</td>
                            <td class="px-5 py-3 text-right">
                                @if($tender->status === \App\Enums\TenderStatus::Ended && $bid->status === \App\Enums\TenderBidStatus::Active)
                                    <form method="POST" action="{{ route('my.tenders.award', [$tender, $bid]) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary text-xs">{{ __('tenders.accept_bid') }}</button>
                                    </form>
                                @elseif($tender->status === \App\Enums\TenderStatus::Awarded && $bid->status === \App\Enums\TenderBidStatus::Won)
                                    <span class="text-xs font-medium text-green-600">{{ __('tenders.bid_status_won') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection