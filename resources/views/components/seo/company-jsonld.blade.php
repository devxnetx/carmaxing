@props(['company', 'listingsCount' => 0])

@php
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'AutoDealer',
        'name' => $company->name,
        'description' => \Illuminate\Support\Str::limit(strip_tags($company->description ?? ''), 300),
        'url' => route('company.show', $company),
        'telephone' => $company->phone,
        'email' => $company->email,
        'foundingDate' => $company->member_since_year ? $company->member_since_year.'-01-01' : null,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $company->address,
            'addressLocality' => $company->city,
            'addressRegion' => $company->region?->name,
            'addressCountry' => 'BG',
        ],
        'areaServed' => ['@type' => 'Country', 'name' => 'Bulgaria'],
        'numberOfEmployees' => null,
        'makesOffer' => $listingsCount > 0 ? [
            '@type' => 'OfferCatalog',
            'name' => $company->name.' — '. __('messages.my_listings'),
            'numberOfItems' => $listingsCount,
        ] : null,
    ];

    if ($company->website) {
        $data['sameAs'] = [$company->website];
    }

    $data = array_filter($data, fn ($v) => $v !== null && $v !== '' && $v !== []);
@endphp
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>