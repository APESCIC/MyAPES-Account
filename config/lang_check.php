<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Translation key check (#276)
    |--------------------------------------------------------------------------
    |
    | Report-only in Wave 1. Flip fail-on-missing in CI during Wave 4 after
    | string extraction. Reuses hard-coded inventory roots ideas from #266.
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
