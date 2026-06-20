<?php

return [
    'social' => [
        'facebook' => [
            'url' => env('SOCIAL_FACEBOOK', 'https://www.facebook.com/carmaxing'),
            'icon' => 'facebook',
            'label' => 'Facebook',
        ],
        'instagram' => [
            'url' => env('SOCIAL_INSTAGRAM', 'https://www.instagram.com/carmaxing'),
            'icon' => 'instagram',
            'label' => 'Instagram',
        ],
        'youtube' => [
            'url' => env('SOCIAL_YOUTUBE', 'https://www.youtube.com/@carmaxing'),
            'icon' => 'youtube',
            'label' => 'YouTube',
        ],
        'tiktok' => [
            'url' => env('SOCIAL_TIKTOK', 'https://www.tiktok.com/@carmaxing'),
            'icon' => 'tiktok',
            'label' => 'TikTok',
        ],
    ],
];