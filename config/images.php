<?php

return [
    'driver' => env('IMAGE_DRIVER', 'gd'),

    'listing' => [
        'thumb' => ['max' => 320, 'quality' => 82],
        'medium' => ['max' => 800, 'quality' => 85],
        'large' => ['max' => 1600, 'quality' => 88],
    ],

    'company_logo' => ['max' => 400, 'quality' => 85],
    'company_cover' => ['max' => 1920, 'quality' => 85],
];