<?php

return [
    'max_months' => 96,
    'min_months' => 12,
    'default_down_payment_percent' => 20,
    'eur_to_bgn' => 1.95583,

    'banks' => [
        [
            'slug' => 'unicredit',
            'name' => 'UniCredit Bulbank',
            'annual_rate' => 7.9,
            'min_down_payment' => 10,
            'max_months' => 96,
        ],
        [
            'slug' => 'dsk',
            'name' => 'DSK Bank',
            'annual_rate' => 8.2,
            'min_down_payment' => 15,
            'max_months' => 84,
        ],
        [
            'slug' => 'fibank',
            'name' => 'Fibank',
            'annual_rate' => 8.5,
            'min_down_payment' => 10,
            'max_months' => 96,
        ],
        [
            'slug' => 'postbank',
            'name' => 'Postbank',
            'annual_rate' => 8.9,
            'min_down_payment' => 20,
            'max_months' => 72,
        ],
        [
            'slug' => 'tbi',
            'name' => 'TBI Bank',
            'annual_rate' => 9.4,
            'min_down_payment' => 0,
            'max_months' => 96,
        ],
        [
            'slug' => 'procredit',
            'name' => 'ProCredit Bank',
            'annual_rate' => 7.5,
            'min_down_payment' => 20,
            'max_months' => 60,
        ],
        [
            'slug' => 'allianz',
            'name' => 'Allianz Bank Bulgaria',
            'annual_rate' => 8.0,
            'min_down_payment' => 15,
            'max_months' => 84,
        ],
        [
            'slug' => 'ccbank',
            'name' => 'Central Cooperative Bank',
            'annual_rate' => 9.1,
            'min_down_payment' => 10,
            'max_months' => 96,
        ],
    ],
];