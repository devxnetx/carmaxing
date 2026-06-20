@props(['listing'])

@php
    $images = $listing->images->map(fn ($img) => $img->url())->values()->all();
    $seller = $listing->company
        ? ['@type' => 'AutoDealer', 'name' => $listing->company->name, 'url' => route('company.show', $listing->company)]
        : ['@type' => 'Person', 'name' => __('messages.private_seller')];

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Car',
        'name' => $listing->composeDisplayTitle(),
        'description' => \Illuminate\Support\Str::limit(strip_tags($listing->description ?? $listing->composeDisplayTitle()), 300),
        'url' => route('listings.show', $listing),
        'image' => $images ?: null,
        'brand' => ['@type' => 'Brand', 'name' => $listing->brand->name],
        'model' => $listing->model->name,
        'vehicleModelDate' => (string) $listing->year,
        'mileageFromOdometer' => $listing->mileage ? [
            '@type' => 'QuantitativeValue',
            'value' => $listing->mileage,
            'unitCode' => 'KMT',
        ] : null,
        'color' => $listing->color_exterior,
        'vehicleInteriorColor' => $listing->color_interior,
        'vehicleTransmission' => $listing->transmission,
        'fuelType' => $listing->fuel_type,
        'driveWheelConfiguration' => $listing->drivetrain,
        'bodyType' => $listing->body_type,
        'offers' => [
            '@type' => 'Offer',
            'price' => $listing->price,
            'priceCurrency' => $listing->currency,
            'availability' => 'https://schema.org/InStock',
            'url' => route('listings.show', $listing),
            'seller' => $seller,
        ],
    ];

    if ($listing->locationLabel()) {
        $isBulgaria = \App\Support\LocationCatalog::isBulgaria($listing->country_code);
        $data['areaServed'] = [
            '@type' => 'Place',
            'name' => $listing->locationLabel(),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $isBulgaria ? $listing->city : null,
                'addressRegion' => $isBulgaria ? $listing->region?->name : null,
                'addressCountry' => $isBulgaria ? 'BG' : $listing->country_code,
            ],
        ];
    }

    $data = array_filter($data, fn ($v) => $v !== null && $v !== '');
@endphp
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>