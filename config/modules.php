<?php

return [
    'lock_wait_seconds' => (int) env('MODULE_LOCK_WAIT_SECONDS', 5),
    'projection_cache_seconds' => (int) env(
        'MODULE_PROJECTION_CACHE_SECONDS',
        30,
    ),
];
