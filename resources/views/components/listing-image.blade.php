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
    $src = $image->url($size);
    $srcset = $image->isRemote() ? null : $image->srcset();
    $sizes = $image->isRemote() ? null : $image->sizesAttribute();
    $w = $width ?? ($size === 'thumb' ? 320 : ($size === 'medium' ? 800 : ($image->width ?: 1600)));
    $h = $height ?? ($size === 'thumb' ? 240 : ($size === 'medium' ? 600 : ($image->height ?: 1200)));
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