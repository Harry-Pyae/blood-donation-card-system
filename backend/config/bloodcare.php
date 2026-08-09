<?php

return [
    'staff_registration_open' => env('BLOODCARE_STAFF_REGISTRATION_OPEN', true),

    'bootstrap_admin' => [
        'name' => env('BLOODCARE_BOOTSTRAP_ADMIN_NAME', 'System Administrator'),
        'email' => env('BLOODCARE_BOOTSTRAP_ADMIN_EMAIL'),
        'password' => env('BLOODCARE_BOOTSTRAP_ADMIN_PASSWORD'),
    ],

    'two_factor' => [
        'issuer' => env('BLOODCARE_TOTP_ISSUER', 'BloodCare'),
        'challenge_ttl' => 300,
        'setup_ttl' => 600,
    ],

    // Operational defaults. Blood services should review these against the
    // responsible national authority before production deployment.
    'donation_intervals_days' => [
        'whole_blood' => 90,
        'platelets' => 14,
        'plasma' => 28,
    ],
];
