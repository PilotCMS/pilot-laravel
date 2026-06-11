<?php

return [
    'default_space' => env('PILOT_DEFAULT_SPACE'),
    'home_slug' => env('PILOT_HOME_SLUG', 'home'),
    'default_locale' => env('PILOT_DEFAULT_LOCALE', env('APP_LOCALE', 'en')),
    'cms_url' => env('PILOT_CMS_URL'),
    'assets' => [
        'base_url' => env('PILOT_ASSET_URL', env('PILOT_CMS_URL')),
    ],
    'views' => [
        'page' => env('PILOT_PAGE_VIEW', 'page'),
        'blocks' => env('PILOT_BLOCKS_VIEW', 'blocks'),
    ],
    'editor_bridge' => [
        'enabled' => env('PILOT_EDITOR_BRIDGE_ENABLED', true),
        'live_preview' => env('PILOT_LIVE_PREVIEW_ENABLED', true),
        'live_root' => env('PILOT_LIVE_PREVIEW_ROOT', '[data-pilot-live-root]'),
    ],
    'in_context' => [
        'enabled' => env('PILOT_IN_CONTEXT_ENABLED', true),
        'path' => env('PILOT_IN_CONTEXT_PATH', '_pilot/in-context'),
    ],
    'preview' => [
        'routes' => env('PILOT_PREVIEW_ROUTES', true),
        'path' => env('PILOT_PREVIEW_PATH', '_pilot/preview'),
        'secret' => env('PILOT_PREVIEW_SECRET'),
        'expires_minutes' => env('PILOT_PREVIEW_EXPIRES_MINUTES', 60),
    ],
];
