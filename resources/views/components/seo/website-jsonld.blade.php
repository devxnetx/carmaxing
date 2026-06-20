@php
    $data = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => url('/#website'),
                'url' => url('/'),
                'name' => config('app.name', 'CARMAXING'),
                'description' => __('messages.footer_mission'),
                'inLanguage' => ['bg-BG', 'en'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => route('search').'?q={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'Organization',
                '@id' => url('/#organization'),
                'name' => config('app.name', 'CARMAXING'),
                'url' => url('/'),
                'logo' => url('/favicon.svg'),
                'sameAs' => collect(config('seo.social'))->pluck('url')->filter()->values()->all(),
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>