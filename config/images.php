<?php

return [
    'driver' => env('IMAGE_DRIVER', 'gd'),

    'listing' => [
        'thumb' => ['max' => 320, 'quality' => 82],
        // Card photo. The grid paints it at about 282×211, so the file stays
        // just above that and is compressed. The ad page uses large.
        'medium' => ['max' => 400, 'quality' => 70],
        'large' => ['max' => 1600, 'quality' => 88],
    ],

    'company_logo' => ['max' => 400, 'quality' => 85],
    'company_cover' => ['max' => 1920, 'quality' => 85],
];