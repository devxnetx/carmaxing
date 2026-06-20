@props(['listing', 'latestPriceChange' => null, 'marketEstimate' => null])

<div class="listing-print-header mb-6 hidden border-b border-gray-300 pb-6 print:block">
    <div class="mb-4 flex items-center justify-between gap-4 text-sm text-gray-600">
        <span class="font-bold text-gray-900">{{ config('app.name', 'CARMAXING') }}</span>
        <span>{{ route('listings.show', $listing) }}</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-900">{{ $listing->vehicleName() }}</h1>
    @if($listing->ad_name)
        <p class="mt-1 text-lg text-gray-600">{{ $listing->ad_name }}</p>
    @endif

    <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            @if($listing->price_on_request)
                <div class="text-xl font-bold">{{ __('messages.price_on_request') }}</div>
            @else
                <div class="text-2xl font-bold">{{ number_format($listing->price) }} {{ __('messages.eur') }}</div>
                <div class="text-sm text-gray-600">{{ number_format($listing->priceInBgn()) }} {{ __('messages.bgn') }}</div>
            @endif
            @if($listing->locationLabel())
                <p class="mt-2 text-sm text-gray-600">{{ $listing->locationLabel() }}</p>
            @endif
        </div>

        <dl class="grid min-w-[14rem] grid-cols-2 gap-x-4 gap-y-1 text-sm">
            @if($listing->displayAdNumber())
                <dt class="text-gray-500">{{ __('messages.ad_number') }}</dt>
                <dd class="text-right font-medium">#{{ $listing->displayAdNumber() }}</dd>
            @endif
            <dt class="text-gray-500">{{ __('messages.views') }}</dt>
            <dd class="text-right font-medium">{{ number_format($listing->views_count) }}</dd>
            @if($listing->published_at)
                <dt class="text-gray-500">{{ __('messages.published_at') }}</dt>
                <dd class="text-right font-medium">{{ $listing->published_at->format('d.m.Y') }}</dd>
            @endif
            <dt class="text-gray-500">{{ __('messages.last_updated') }}</dt>
            <dd class="text-right font-medium">{{ $listing->updated_at->format('d.m.Y H:i') }}</dd>
        </dl>
    </div>

    @isset($latestPriceChange)
        <p class="mt-3 text-sm text-gray-600">
            {{ __('messages.price_was', ['old' => number_format($latestPriceChange->old_price), 'new' => number_format($latestPriceChange->new_price)]) }}
        </p>
    @endisset

    @isset($marketEstimate)
        @if($marketEstimate['delta'] > 0)
            <p class="mt-2 text-sm text-gray-700">{{ __('messages.market_value_below', ['amount' => number_format($marketEstimate['delta'])]) }}</p>
        @elseif($marketEstimate['delta'] < 0)
            <p class="mt-2 text-sm text-gray-700">{{ __('messages.market_value_above', ['amount' => number_format(abs($marketEstimate['delta']))]) }}</p>
        @endif
    @endisset

    @if($listing->company || $listing->contactPhone())
        <div class="mt-4 border-t border-gray-200 pt-4 text-sm">
            <strong>{{ __('messages.seller_info') }}:</strong>
            @if($listing->company)
                {{ $listing->company->name }}
            @else
                {{ __('messages.private_seller') }}
            @endif
            @if($listing->contactPhone())
                · {{ $listing->contactPhone() }}
            @endif
        </div>
    @endif
</div>