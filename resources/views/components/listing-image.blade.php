@props([
    'image',
    'size' => 'medium',
    'alt' => '',
    'lazy' => true,
    'priority' => false,
    'class' => '',
    'width' => null,
    'height' => null,
])

@php
    $variants = config('images.listing');
    $src = $image->url($size);
    // Cards ask for one file. A srcset that also offers the 1600px photo
    // makes the browser download that photo for a ~282px slot.
    $srcset = ($image->isRemote() || $size !== 'large') ? null : $image->srcset();
    $sizes = $srcset ? $image->sizesAttribute() : null;
    $w = $width ?? match ($size) {
        'thumb' => (int) $variants['thumb']['max'],
        'medium' => (int) $variants['medium']['max'],
        default => $image->width ?: (int) $variants['large']['max'],
    };
    $h = $height ?? ($size === 'large'
        ? ($image->height ?: (int) round($w * 3 / 4))
        : (int) round($w * 3 / 4));
@endphp

<img
    src="{{ $src }}"
    @if($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
    alt="{{ $alt }}"
    width="{{ $w }}"
    height="{{ $h }}"
    @if($lazy && ! $priority) loading="lazy" @endif
    @if($priority) fetchpriority="high" @endif
    decoding="async"
    {{ $attributes->merge(['class' => $class]) }}
>