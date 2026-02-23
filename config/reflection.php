<?php

return [
    // Shared layer from core that can be reflected into downstream apps.
    'shared_paths' => [
        'app/Support',
        'app/Http/Middleware/TrackUserActivity.php',
        'app/Http/Controllers/UiOptionsController.php',
        'app/Http/Controllers/MediaManagerController.php',
        'config/haarray.php',
        'config/menu.php',
        'public/css/haarray.app.css',
        'public/css/haarray.bootstrap-bridge.css',
        'public/css/haarray.starter.css',
        'public/js/haarray.app.js',
        'public/js/haarray.js',
        'public/js/haarray.plugins.js',
        'resources/views/components',
        'resources/views/layouts/app.blade.php',
        'resources/views/layouts/haarray.blade.php',
        'resources/views/docs/starter-kit.blade.php',
        'docs',
        'server.php',
        '.htaccess',
    ],

    // Downstream targets.
    'targets' => [
        'log' => [
            'path' => env('HAARRAY_REFLECT_LOG_PATH', base_path('../log')),
            'remote' => env('HAARRAY_REFLECT_LOG_REMOTE', 'origin'),
            'branch' => env('HAARRAY_REFLECT_LOG_BRANCH', 'main'),
            'local_config_file' => '.haarray-reflection.php',
            'extra_shared_paths' => [],
            'exclude_paths' => [],
        ],
    ],
];
