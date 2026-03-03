<?php

return [
    'currency' => 'USD',

    'listing_base' => [
        'home' => 120,
        'condo' => 80,
        'rental' => 60,
        'chalet' => 140,
        'other' => 90,
    ],

    'services' => [
        'photo' => 180,
        'video' => 220,
        'drone' => 140,
        'floor_plan' => 90,
        'social_media' => 110,
    ],

    'photo_count' => [
        'up_to_20' => 0,
        '21_30' => 50,
        '31_45' => 100,
        '46_plus' => 180,
    ],

    'video_type' => [
        'walkthrough' => 0,
        'cinematic' => 120,
        'reel' => 70,
    ],

    'drone_mode' => [
        'photo' => 0,
        'video' => 60,
        'both' => 100,
    ],

    'add_ons' => [
        'virtual_staging' => 120,
        'day_to_dusk' => 70,
        'priority_editing' => 90,
    ],

    'service_notes' => [
        'photo' => 'Photos are typically delivered in 24-48h.',
        'video' => 'Video delivery is typically around 72h.',
        'drone' => 'Drone capture depends on weather and flight conditions.',
        'floor_plan' => 'Floor plans are optimized for listing clarity.',
        'social_media' => 'Social cuts are optimized for short-form performance.',
    ],

    'package_presets' => [
        'essential' => [
            'title' => 'Essential',
            'display_total' => '249.99',
            'estimated_total' => 250,
            'listing_type' => 'condo',
            'services' => ['photo'],
            'line_items' => [
                ['label' => 'Essential package', 'amount' => 250],
                ['label' => 'Up to 30 HDR images', 'amount' => 0],
                ['label' => 'Basic retouching', 'amount' => 0],
                ['label' => '24h delivery', 'amount' => 0],
                ['label' => 'MLS-ready formatting', 'amount' => 0],
            ],
            'notes' => [
                'Preset package price is fixed.',
                'No extra options are added for this package.',
            ],
        ],
        'signature' => [
            'title' => 'Signature',
            'display_total' => '349.99',
            'estimated_total' => 350,
            'listing_type' => 'home',
            'services' => ['photo', 'drone', 'video'],
            'line_items' => [
                ['label' => 'Signature package', 'amount' => 350],
                ['label' => 'Up to 25 HDR images', 'amount' => 0],
                ['label' => 'Up to 7 drone images', 'amount' => 0],
                ['label' => 'Video teaser (MLS + social)', 'amount' => 0],
                ['label' => '24h delivery', 'amount' => 0],
            ],
            'notes' => [
                'Preset package price is fixed.',
                'No extra options are added for this package.',
            ],
        ],
        'prestige' => [
            'title' => 'Prestige',
            'display_total' => '499.99',
            'estimated_total' => 500,
            'listing_type' => 'home',
            'services' => ['photo', 'drone', 'video', 'floor_plan', 'social_media'],
            'line_items' => [
                ['label' => 'Prestige package', 'amount' => 500],
                ['label' => 'Up to 30 HDR images', 'amount' => 0],
                ['label' => 'Up to 10 drone images', 'amount' => 0],
                ['label' => 'Cinematic walkthrough video', 'amount' => 0],
                ['label' => 'Social reel cut + floor plan', 'amount' => 0],
            ],
            'notes' => [
                'Preset package price is fixed.',
                'No extra options are added for this package.',
            ],
        ],
    ],
];
