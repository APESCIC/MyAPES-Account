<?php

$appUrl = (string) config('app.url');
$appHost = parse_url($appUrl, PHP_URL_HOST);

return [

    /*
    |--------------------------------------------------------------------------
    | Relying Party ID
    |--------------------------------------------------------------------------
    |
    | Derived from APP_URL. Passkeys are cryptographically bound to this host.
    | Live host: myaccount.myapes.me.uk — changing rpId invalidates all passkeys.
    |
    */

    'relying_party_id' => is_string($appHost) && $appHost !== '' ? $appHost : 'localhost',

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Origins permitted to complete WebAuthn ceremonies (full APP_URL origin).
    |
    */

    'allowed_origins' => array_values(array_filter([
        rtrim($appUrl, '/'),
    ])),

    /*
    |--------------------------------------------------------------------------
    | User Handle Secret
    |--------------------------------------------------------------------------
    |
    | Secret used to derive a stable WebAuthn user handle from each user model.
    | Set this explicitly if you rotate your application key.
    |
    */

    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => 60000,

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Passkeys Routes Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Passkeys Management Middleware
    |--------------------------------------------------------------------------
    |
    | Step-up (#233) via account.step-up, plus local-identity gate for enrolment.
    |
    */

    'management_middleware' => ['account.step-up', 'passkeys.local'],

    /*
    |--------------------------------------------------------------------------
    | Passkeys Throttling
    |--------------------------------------------------------------------------
    */

    'throttle' => 'throttle:passkeys',

    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    'redirect' => '/dashboard',

];
