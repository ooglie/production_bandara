<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media disks
    |--------------------------------------------------------------------------
    |
    | Customer-facing catalogue, recipe, avatar and homepage media is always
    | written to the public disk. Ticket attachments and migration reports stay
    | on the private local disk and are served only through authorised routes.
    |
    */
    'public_disk' => env('MEDIA_PUBLIC_DISK', 'public'),
    'private_disk' => env('MEDIA_PRIVATE_DISK', 'local'),

    /* Public media is grouped below storage/app/public/media. */
    'public_root' => 'media',

    /* Private reports are written below storage/app/private/media-migrations. */
    'migration_reports_dir' => 'media-migrations',

    'paths' => [
        'products' => 'products',
        'recipes' => 'recipes',
        'avatars' => 'avatars',
        'home' => 'home',
        'product_collections' => 'product-collections',
        'announcements' => 'announcements',
        'tickets' => 'tickets',
    ],

    /*
    | Legacy roots scanned by bandara:organize-media when producing the
    | unreferenced-files report. New media/* paths are deliberately excluded.
    */
    'legacy_roots' => [
        'public' => [
            'products',
            'recipes',
            'avatars',
            'home',
            'images/home',
            'images/hero',
            'product-collections',
            'announcements',
            'tickets/attachments',
        ],
        'local' => [
            'products',
            'recipes',
            'avatars',
            'home',
            'product-collections',
            'announcements',
            'tickets/attachments',
        ],
    ],
];
