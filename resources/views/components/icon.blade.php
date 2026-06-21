@props([
    'name',
    'variant' => 'outline',
])

@php
    $brands = ['facebook', 'instagram', 'youtube', 'tiktok', 'whatsapp', 'viber'];
    $isBrand = in_array($name, $brands, true);
    $isSolid = $variant === 'solid' || $isBrand;
@endphp

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    @if($isSolid)
        fill="currentColor"
    @else
        fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
    @endif
    {{ $attributes->class('shrink-0') }}
    aria-hidden="true"
>
    @switch($name)
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('x')
            <path d="M6 6l12 12M18 6 6 18" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('heart')
            @if($isSolid)
                <path d="M12 21s-6.7-4.35-9.33-8.1C.74 10.1 2.03 6.5 5.5 5.2c2.03-.77 4.28.12 5.5 1.9 1.22-1.78 3.47-2.67 5.5-1.9 3.47 1.3 4.76 4.9 2.83 7.7C18.7 16.65 12 21 12 21z" />
            @else
                <path d="M12 20.5c-4.2-3.1-7.5-6.2-9.2-8.8-2.1-3.2-.6-7.2 2.9-8.4 1.8-.7 3.8-.1 5.1 1.3 1.3-1.4 3.3-2 5.1-1.3 3.5 1.2 5 5.2 2.9 8.4-1.7 2.6-5 5.7-9.2 8.8z" />
            @endif
            @break
        @case('cog')
            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" />
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1A1.7 1.7 0 0 0 11 3.2V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z" />
            @break
        @case('dashboard')
            <path d="M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z" />
            @break
        @case('building')
            <path d="M4 21V5a1 1 0 0 1 1-1h5v17M10 21V9h4v12M14 21V3h5a1 1 0 0 1 1 1v17M8 8h.01M8 12h.01M8 16h.01M16 8h.01M16 12h.01M16 16h.01" />
            @break
        @case('store')
            <path d="M4 10V7l2-4h12l2 4v3M4 10h16v10H4zM9 14h6" />
            @break
        @case('logout')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
            @break
        @case('chevron-left')
            <path d="M15 6l-6 6 6 6" />
            @break
        @case('chevron-right')
            <path d="M9 6l6 6-6 6" />
            @break
        @case('map-pin')
            <path d="M12 21s6-5.33 6-10a6 6 0 1 0-12 0c0 4.67 6 10 6 10z" />
            <circle cx="12" cy="11" r="2.5" />
            @break
        @case('print')
            <path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <path d="M7 14h10v7H7z" />
            @break
        @case('share')
            <path d="M16 8a3 3 0 1 0-2.8-4M8 12a3 3 0 1 0 2.8 4M16 16a3 3 0 1 0-2.8-4M8.2 10.7l7.6 2.6M8.2 13.3l7.6-2.6" />
            @break
        @case('link')
            <path d="M10 14a4 4 0 0 1 0-5.7l1.3-1.3a4 4 0 0 1 5.7 5.7L16 13M14 10a4 4 0 0 1 0 5.7l-1.3 1.3a4 4 0 0 1-5.7-5.7L8 11" />
            @break
        @case('flag')
            <path d="M5 3v18M5 4h12l-2 3 2 3H5" />
            @break
        @case('send')
            <path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z" />
            @break
        @case('image')
            <rect x="4" y="5" width="16" height="14" rx="2" />
            <circle cx="9" cy="10" r="1.5" />
            <path d="M20 17l-5-5-4 4-2-2-5 5" />
            @break
        @case('video')
            <rect x="3" y="6" width="14" height="12" rx="2" />
            <path d="M17 10l4-2v8l-4-2z" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5 20c1.5-3.5 4.5-5.5 7-5.5s5.5 2 7 5.5" />
            @break
        @case('phone')
            <path d="M6.5 4h3l1.5 5-2 1.5a11 11 0 0 0 5 5L13.5 14l5 1.5v3a2 2 0 0 1-2.2 2 16 16 0 0 1-12.3-8.5A2 2 0 0 1 6.5 4z" />
            @break
        @case('facebook')
            <path d="M13.5 22v-8.5H16V11h-2.5V8.8c0-1.2.7-2.3 2.4-2.3H16V4.1c-.3 0-1.4-.1-2.7-.1-2.7 0-4.6 1.6-4.6 4.7V11H6.5v2.5H9v8.5h4.5z" />
            @break
        @case('instagram')
            <path d="M8 3h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8a5 5 0 0 1 5-5zm0 2.2A2.8 2.8 0 0 0 5.2 8v8A2.8 2.8 0 0 0 8 18.8h8a2.8 2.8 0 0 0 2.8-2.8V8A2.8 2.8 0 0 0 16 5.2H8zM12 8.8A3.2 3.2 0 1 1 8.8 12 3.2 3.2 0 0 1 12 8.8zm0 1.8A1.4 1.4 0 1 0 13.4 12 1.4 1.4 0 0 0 12 10.6zm4.9-4.3a1 1 0 1 1-1 1 1 1 0 0 1 1-1z" />
            @break
        @case('youtube')
            <path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C17.7 5 12 5 12 5s-5.7 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C6.3 19 12 19 12 19s5.7 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15.5V8.5l5.5 3.5L10 15.5z" />
            @break
        @case('tiktok')
            <path d="M14.5 4h2.4v3.8a5.8 5.8 0 0 0 3.6-1.2v2.8a8.3 8.3 0 0 1-3.6.9v5.9a5.5 5.5 0 1 1-5.5-5.5h.3v2.8a2.7 2.7 0 1 0 1.9 2.6V4z" />
            @break
        @case('whatsapp')
            <path d="M12 2a10 10 0 0 0-8.7 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm0 1.8a8.2 8.2 0 0 1 6.6 13l-.3.5-.5.2a8.2 8.2 0 0 1-11-11l.2-.5.5-.3A8.1 8.1 0 0 1 12 3.8zm-2.8 4.5c-.2 0-.5.1-.7.4-.2.3-.9 1-.9 2.4s1 2.8 1.1 3c.1.2 1.9 3 4.7 4.1 2.3.9 2.8.7 3.3.7.5 0 1.6-.6 1.8-1.2.2-.6.2-1.1.1-1.2-.1-.1-.2-.1-.5-.3s-1.8-.9-2.1-1c-.3-.1-.5-.1-.7.1-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.7.1-.1.3-.3.4-.5.1-.1.1-.3 0-.4 0-.1-.7-1.8-1-2.5-.2-.6-.5-.5-.7-.5z" />
            @break
        @case('viber')
            <path d="M12 2C7 2 2.8 5.8 2.2 10.7 1.6 15.6 4 20.2 8 22l-1 3 3.4-.9c1 .3 2 .4 3 .4 5 0 9.2-3.8 9.8-8.7S17 2 12 2zm.2 4.5c3.8 0 6.9 2.8 7.3 6.4.4 3.6-2.3 6.8-6.1 7.2-.8.1-1.6.1-2.4 0l-2 .5.5-1.9c-2.8-1.5-4.4-4.4-4-7.5.4-3.1 3.4-5.5 6.7-5.7zm-2.1 3.2c-.2 0-.4.2-.5.5l-.2 1.1c0 .3.1.6.4.8l1 .6c.2.1.3.4.2.6l-.4 1.1c-.1.3.1.6.4.7.5.2 1.8.7 2.8.1.9-.5 2.1-2 2.4-2.8.1-.3 0-.6-.3-.7l-1.1-.4c-.2-.1-.5 0-.6.2l-.5.9c-.1.2-.4.2-.6 0l-.9-.7c-.2-.2-.2-.5 0-.7l.7-.9c.2-.2.1-.5-.1-.6l-1-.5c-.3-.1-.4-.4-.3-.7l.3-1.1c.1-.3-.1-.6-.4-.6z" />
            @break
        @case('check')
            <path d="M20 6 9 17l-5-5" />
            @break
        @case('save')
            <path d="M5.25 4.5v15m0-15h10.125a1.125 1.125 0 011.125 1.125v15M5.25 4.5h10.125M9.75 8.25h4.5" />
            @break
        @case('bell')
            <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.7 21a2 2 0 0 1-3.4 0" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8" />
            <path d="M12 8v4l3 2" />
            @break
        @case('compare')
            <path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M12 8v8" />
            @break
        @case('home')
            <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="6" />
            <path d="M20 20l-4-4" />
            @break
        @case('list')
            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
            @break
        @case('activity')
            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
            @break
        @case('grid')
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
            @break
        @case('map')
            <path d="M9 18 3 20V6l6-2 6 2 6-2v14l-6 2-6-2z" />
            <path d="M9 4v14M15 6v14" />
            @break
        @case('eye')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" />
            <circle cx="12" cy="12" r="2.5" />
            @break
        @case('eye-off')
            <path d="M3 3l18 18M10.6 10.6A2.5 2.5 0 0 0 12 14.5a2.5 2.5 0 0 0 1.9-.9M9.9 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18.2 18.2 0 0 1-4.1 5.2M6.1 6.1A18.5 18.5 0 0 0 2 12s3.5 7 10 7a10.2 10.2 0 0 0 4.2-.9" />
            @break
    @endswitch
</svg>