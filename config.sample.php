<?php
/**
 * Copy this file to config.php and fill in your values.
 * config.php is git-ignored so your passwords never get committed.
 */
return [
    // ---- MySQL ----
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'engagement',
        'user'     => 'engagement_user',
        'password' => 'change-me',
    ],

    // ---- Admin page (/admin.php) ----
    // Use a long, private password. Wishes are only visible after logging in.
    'admin_password' => 'change-this-admin-password',

    // ---- Event details shown in the app ----
    'event' => [
        'bride'    => 'Prathyusha',
        'groom'    => 'Vignesh',
        'date'     => '15 November 2026',
        'city'     => 'Chennai',
        'families' => [
            'Usha & Rammohan',
            'Krithika & Deepak',
            'Anirudh Deepak',
        ],
    ],

    // ---- Uploads ----
    'max_upload_mb'   => 10,     // largest photo a guest may send
    'max_message_len' => 500,
    'max_name_len'    => 80,
];
