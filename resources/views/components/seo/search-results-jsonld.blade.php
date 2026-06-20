@props(['listings', 'name' => null, 'description' => null, 'url' => null])

@php
    $pageUrl = $url ?? url()->current();
    $pageName = $name ?? __('messages.search').' — '.config('app.name');
    $pageDescription = $description ?? __('messages.tagline');

    $items = $listings->map(fn ($listing, $i) => [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'url' => route('listings.show', $listing),
        'name' => $listing->composeDisplayTitle(),
    ])->values()->all();

    $data = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebPage',
                '@id' => $pageUrl.'#webpage',
                'url' => $pageUrl,
                'name' => $pageName,
                'description' => $pageDescription,
                'isPartOf' => [
                    '@type' => 'WebSite',
                    'name' => config('app.name'),
                    'url' => route('home'),
                ],
            ],
            [
                '@type' => 'ItemList',
                '@id' => $pageUrl.'#itemlist',
                'name' => $pageName,
                'description' => $pageDescription,
                'numberOfItems' => $listings->total(),
                'itemListElement' => $items,
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>