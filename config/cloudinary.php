<?php

return [
    'cloud_url' => env('CLOUDINARY_URL'),
    
    // Default transformations for different image types
    'transformations' => [
        'event_thumbnail' => [
            'width' => 400,
            'height' => 300,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'event_banner' => [
            'width' => 1200,
            'height' => 400,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'event_card' => [
            'width' => 600,
            'height' => 400,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'organizer_logo' => [
            'width' => 200,
            'height' => 200,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'user_avatar' => [
            'width' => 150,
            'height' => 150,
            'crop' => 'thumb',
            'gravity' => 'face',
            'quality' => 'auto',
            'format' => 'auto',
        ],
    ],
];