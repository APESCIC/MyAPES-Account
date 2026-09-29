<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Translation key check (#276)
    |--------------------------------------------------------------------------
    |
    | CI runs `php artisan lang:check --fail-on-missing`. Unused keys are
    | reported but never fail the build. Reuses hard-coded inventory roots
    | ideas from #266.
    |
    */

    'locale' => 'en_GB',

    'scan_roots' => [
        'app',
        'resources/views',
        'routes',
        'plugins',
        'modules',
    ],

    'ignore_path_prefixes' => [
        'vendor/',
        'node_modules/',
        'storage/',
        'bootstrap/cache/',
        'tests/',
    ],

    /*
    | Prefixes that should not appear in the unused-key report (framework files,
    | glossary terms, and plugin metadata used via label() / keywords later).
    */
    'unused_allowlist' => [
        'terms.',
        'validation.',
        'auth.',
        'passwords.',
        'pagination.',
        '::plugin.',
    ],
];
