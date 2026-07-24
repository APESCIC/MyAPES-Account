<?php

$parseCsv = static function (?string $raw): array {
    if (! is_string($raw) || trim($raw) === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw))));
};

return [
    'oidc' => [
        'issuer' => env('OIDC_ISSUER'),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'redirect_uri' => env('OIDC_REDIRECT_URI'),
        'scopes' => $parseCsv(env('OIDC_SCOPES', 'openid,profile,email')),
    ],

    'ldap' => [
        'host' => env('LDAP_HOST'),
        'port' => (int) env('LDAP_PORT', 389),
        'base_dn' => env('LDAP_BASE_DN'),
        'bind_dn' => env('LDAP_BIND_DN'),
        'bind_password' => env('LDAP_BIND_PASSWORD'),
        'user_filter' => env('LDAP_USER_FILTER', '(mail=%s)'),
        'group_attribute' => env('LDAP_GROUP_ATTRIBUTE', 'memberOf'),
        'start_tls' => (bool) env('LDAP_START_TLS', false),
    ],

    'roles' => [
        'staff_groups' => $parseCsv(env('OIDC_STAFF_GROUPS', 'position.staff,position.students,position.volunteers')),
        'admin_groups' => $parseCsv(env('OIDC_ADMIN_GROUPS', 'admin,superadmin')),
        'superadmin_groups' => $parseCsv(env('OIDC_SUPERADMIN_GROUPS', 'superadmin')),
    ],

    'audit' => [
        'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 180),
    ],
];
