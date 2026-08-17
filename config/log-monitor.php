<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for the package. When disabled, routes are not
    | registered and the log monitor cannot be accessed.
    |
    */

    'enabled' => env('LOG_MONITOR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Logs path
    |--------------------------------------------------------------------------
    |
    | The absolute directory the package is allowed to read logs from.
    | All file resolution is confined to this directory; no file outside
    | of it (following symlinks) can ever be read, downloaded or cleared.
    |
    */

    'path' => storage_path('logs'),

    /*
    |--------------------------------------------------------------------------
    | Route configuration
    |--------------------------------------------------------------------------
    */

    'route' => [
        'prefix' => 'system/logs',

        'middleware' => [
            'web',
            'auth',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed extensions
    |--------------------------------------------------------------------------
    |
    | Only files with one of these extensions will ever be listed, read,
    | downloaded or cleared.
    |
    */

    'allowed_extensions' => [
        'log',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log levels
    |--------------------------------------------------------------------------
    */

    'levels' => [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => 50,
        'options' => [25, 50, 100, 250],
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    'allow_download' => true,

    'allow_clear' => false,

    /*
    |--------------------------------------------------------------------------
    | Auto refresh
    |--------------------------------------------------------------------------
    */

    'auto_refresh' => false,

    'auto_refresh_interval' => 10,

    /*
    |--------------------------------------------------------------------------
    | Reading limits
    |--------------------------------------------------------------------------
    |
    | Safety limits to keep the package stable on very large log files.
    | max_bytes_scanned caps how much of a file is read from the tail
    | when listing/searching entries.
    |
    */

    'limits' => [
        'max_bytes_scanned' => 10 * 1024 * 1024, // 10 MB
        'max_entries' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization gate
    |--------------------------------------------------------------------------
    |
    | Optional Gate ability name. If defined by the host application, it
    | will be checked in addition to the route middleware.
    |
    */

    'authorization_gate' => 'viewLaravelLogs',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 5, // seconds
    ],

];
