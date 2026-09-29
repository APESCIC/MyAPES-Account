<?php

/**
 * Local public username policy (#227).
 *
 * Charset and length are enforced by App\Rules\Username; reserved words are
 * case-insensitive matches after normalisation (lowercase + trim).
 */
return [
    'min_length' => 3,
    'max_length' => 30,

    /*
    | Pattern after lowercasing: start and end with alphanumeric; middle may
    | use letters, digits, dots, underscores, or hyphens.
    */
    'pattern' => '/^[a-z0-9](?:[a-z0-9._-]{1,28}[a-z0-9])$/',

    'reserved' => [
        'admin',
        'administrator',
        'api',
        'auth',
        'cloudron',
        'help',
        'login',
        'logout',
        'mail',
        'me',
        'moderator',
        'myapes',
        'null',
        'owner',
        'password',
        'profile',
        'register',
        'root',
        'security',
        'staff',
        'superadmin',
        'support',
        'system',
        'undefined',
        'webmaster',
        'www',
    ],
];
