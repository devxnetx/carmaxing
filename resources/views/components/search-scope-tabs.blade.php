@props(['scope', 'filters' => [], 'target' => 'results'])

@php
    use App\Enums\SearchScope;

    $query = collect($filters)
        ->except(['page', 'sort', 'view', 'map_lat', 'map_lng', 'radius_km', 'scope'])
        ->filter(fn ($value) => $value !== null && $value !== '' && $value !== [])
        ->all();
@endphp

<div class="search-scope-tabs" role="tablist" aria-label="{{ __('messages.search_scope_label') }}">
    @foreach(SearchScope::casesOrdered() as $tabScope)
        @php
            $href = match ($target) {
                'form' => route('search.form').'?'.http_build_query(array_merge($query, ['scope' => $tabScope->value])),
                'home' => route('home').'?'.http_build_query(array_merge($query, ['scope' => $tabScope->value])),
                default => $query === []
                    ? route($tabScope->resultsRouteName())
                    : route($tabScope->resultsRouteName()).'?'.http_build_query($query),
            };
            $isActive = $scope === $tabScope;
        @endphp
        <a
            href="{{ $href }}"
            role="tab"
            aria-selected="{{ $isActive ? 'true' : 'false' }}"
            @class([
                'search-scope-tab',
                'search-scope-tab-active' => $isActive,
            ])
        >
            {{ $tabScope->label() }}
        </a>
    @endforeach
</div>