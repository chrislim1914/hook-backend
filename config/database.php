<?php

return array(

    'default' => 'mysql',

    'connections' => array(

        # primary database connection
        'mysql' => array(
            'driver'    => env('DB_MYSQL_DRIVER'),
            'host'      => env('DB_MYSQL_HOST'),
            'database'  => env('DB_MYSQL_NAME'),
            'username'  => env('DB_MYSQL_USERNAME'),
            'password'  => env('DB_MYSQL_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ),
    ),

    'migrations' => 'migrations'
);