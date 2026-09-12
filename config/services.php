<?php

return [
    'sso' => [
        'base_url' => env('SSO_BASE_URL', 'https://sekalilogin.gotechdynamics.com'),
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'field_map' => [
            'nik' => env('SSO_NIK_FIELD', 'nik'),
            'name' => env('SSO_NAME_FIELD', 'name'),
            'avatar_url' => env('SSO_AVATAR_FIELD', 'avatar_url'),
            'active' => env('SSO_ACTIVE_FIELD', 'active'),
        ],
    ],
];
