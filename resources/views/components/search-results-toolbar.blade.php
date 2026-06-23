@props([
    'total',
    'scope',
    'correctSearchUrl',
    'showSaveSearch' => true,
    'showViewModes' => true,
    'showSort' => true,
    'sortOptions' => null,
    'viewMode' => 'grid',
])

@php
    use App\Enums\SearchScope;

    $sortOptions ??= match ($scope) {
        SearchScope::Auctions => [
            'ending_soon' => __('messages.sort_ending_soon'),
            'price_asc' => __('messages.sort_price_asc'),
            'price_desc' => __('messages.sort_price_desc'),
            'year_desc' => __('messages.sort_year_desc'),
            'newest' => __('messages.sort_newest'),
        ],
        SearchScope::Imports => [
            'ending_soon' => __('messages.sort_ending_soon'),
            'newest' => __('messages.sort_newest'),
            'price_asc' => __('messages.sort_price_asc'),
            'price_desc' => __('messages.sort_price_desc'),
            'year_desc' => __('messages.sort_year_desc'),
            'mileage_asc' => __('messages.sort_mileage_asc'),
        ],
        default => [
            'newest' => __('messages.sort_newest'),
            'price_asc' => __('messages.sort_price_asc'),
            'price_desc' => __('messages.sort_price_desc'),
            'year_desc' => __('messages.sort_year_desc'),
            'mileage_asc' => __('messages.sort_mileage_asc'),
        ],
    };
@endphp

<div class="mt-6 flex flex-wrap items-center justify-between gap-4">
    <p class="text-sm text-[var(--color-text-muted)]">
        {{ number_format($total) }} {{ __('messages.results') }}
    </p>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ $correctSearchUrl }}" class="btn-secondary text-xs">
            <x-icon name="cog" class="h-4 w-4" /> {{ __('messages.correct_this_search') }}
        </a>

        @if($showSaveSearch && in_array($scope, [SearchScope::Listings, SearchScope::Imports], true))
            @auth
                <form method="POST" action="{{ route('saved-searches.store') }}" class="inline">
                    @csrf
                    <input type="hidden" name="scope" value="{{ $scope->value }}">
                    @foreach(request()->except('page') as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $v)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <button type="submit" class="btn-secondary text-xs">
                        <x-icon name="bell" class="h-4 w-4" /> {{ __('messages.save_search') }}
                    </button>
                </form>
            @endauth
        @endif

        @if($showViewModes)
            <div class="flex rounded-lg border border-[var(--color-border)] text-xs">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'list', 'page' => null]) }}" class="hidden items-center gap-1 px-3 py-2 sm:flex {{ $viewMode === 'list' ? 'bg-brand-600 text-white rounded-lg' : '' }}">
                    <x-icon name="list" class="h-4 w-4" /> {{ __('messages.view_list') }}
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid', 'page' => null]) }}" class="flex items-center gap-1 px-3 py-2 {{ $viewMode === 'grid' ? 'bg-brand-600 text-white rounded-lg' : '' }}">
                    <x-icon name="grid" class="h-4 w-4" /> {{ __('messages.view_grid') }}
                </a>
            </div>
        @endif

        @if($showSort)
            <form method="GET" class="flex items-center gap-2">
                @foreach(request()->except('sort', 'page') as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <select name="sort" class="input w-auto" onchange="this.form.submit()">
                    @foreach($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('sort', array_key_first($sortOptions)) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>
</div>