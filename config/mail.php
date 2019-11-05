<?php

return [
    'driver' => env('MAIL_DRIVER', 'smtp'),
    'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
    'port' => env('MAIL_PORT', 587),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS'), 
        'name' => env('APP_NAME')
    ],
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'username' => env('MAIL_FROM_ADDRESS'),
    'password' => env('MAIL_PASSWORD'),
    'markdown' => [
        'theme' => 'default',
        'paths' => [
                    resource_path('views/'),
                    ],
        ],
];
