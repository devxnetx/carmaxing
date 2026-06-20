<?php

return [

    'consent_version' => 1,

    'consent_cookie' => 'carmaxing_consent',

    'consent_max_age_days' => 365,

    'categories' => [
        'necessary' => [
            'required' => true,
        ],
        'functional' => [
            'required' => false,
        ],
        'analytics' => [
            'required' => false,
            'enabled' => false,
        ],
        'marketing' => [
            'required' => false,
            'enabled' => false,
        ],
    ],

    'inventory' => [
        [
            'name' => 'carmaxing_consent',
            'category' => 'necessary',
            'provider' => 'CARMAXING',
            'purpose_key' => 'cookie_inventory_consent_purpose',
            'duration_key' => 'cookie_inventory_consent_duration',
        ],
        [
            'name' => 'laravel_session',
            'category' => 'necessary',
            'provider' => 'CARMAXING',
            'purpose_key' => 'cookie_inventory_session_purpose',
            'duration_key' => 'cookie_inventory_session_duration',
        ],
        [
            'name' => 'XSRF-TOKEN',
            'category' => 'necessary',
            'provider' => 'CARMAXING',
            'purpose_key' => 'cookie_inventory_csrf_purpose',
            'duration_key' => 'cookie_inventory_csrf_duration',
        ],
        [
            'name' => 'locale',
            'category' => 'functional',
            'provider' => 'CARMAXING',
            'purpose_key' => 'cookie_inventory_locale_purpose',
            'duration_key' => 'cookie_inventory_locale_duration',
        ],
        [
            'name' => 'theme',
            'category' => 'functional',
            'provider' => 'CARMAXING',
            'purpose_key' => 'cookie_inventory_theme_purpose',
            'duration_key' => 'cookie_inventory_theme_duration',
        ],
        [
            'name' => 'remember_web_*',
            'category' => 'necessary',
            'provider' => 'CARMAXING',
            'purpose_key' => 'cookie_inventory_remember_purpose',
            'duration_key' => 'cookie_inventory_remember_duration',
        ],
    ],

];