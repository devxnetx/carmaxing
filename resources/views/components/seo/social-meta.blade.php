@props([
    'title',
    'description',
    'url',
    'image',
    'imageAlt' => null,
    'type' => 'website',
])

@php
    $imageAlt ??= $title;
    $locale = app()->getLocale() === 'bg' ? 'bg_BG' : 'en_US';
    $alternateLocale = $locale === 'bg_BG' ? 'en_US' : 'bg_BG';
@endphp

<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ config('app.name', 'CARMAXING') }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:secure_url" content="{{ $image }}">
<meta property="og:image:alt" content="{{ $imageAlt }}">
<meta property="og:locale" content="{{ $locale }}">
<meta property="og:locale:alternate" content="{{ $alternateLocale }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:image:alt" content="{{ $imageAlt }}">

<link rel="image_src" href="{{ $image }}">