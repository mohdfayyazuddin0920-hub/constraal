<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', env('APPSETTING_DB_HOST', 'localhost')),
            'port' => env('DB_PORT', env('APPSETTING_DB_PORT', 3306)),
            'database' => env('DB_DATABASE', env('APPSETTING_DB_DATABASE', 'constraal')),
            'username' => env('DB_USERNAME', env('APPSETTING_DB_USERNAME', 'root')),
            'password' => env('DB_PASSWORD', env('APPSETTING_DB_PASSWORD', '')),
            'unix_socket' => env('DB_SOCKET', env('APPSETTING_DB_SOCKET', '')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA')
                    ? (str_starts_with(env('MYSQL_ATTR_SSL_CA'), '/') ? env('MYSQL_ATTR_SSL_CA') : base_path(env('MYSQL_ATTR_SSL_CA')))
                    : null,
                PDO::ATTR_TIMEOUT => (int) env('DB_QUERY_TIMEOUT', 5),
            ]) : [],
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],
    ],
    'migrations' => 'migrations',
];
