<?php

$configs = [
    'database' => [
        'host' => getenv('BLOG_DB_HOST') ?? 'mysql',
        'username' => getenv('BLOG_DB_USER') ?? 'root',
        'password' => getenv('BLOG_DB_PASSWORD') ?? 'bhunter',
        'database' => 'vue-blog',
        'port' => 3306
    ],
    'crypt' => [
        'cipher' => 'blowfish',
        'key' => 'OUYTRfghjncder%^&*()PKfrtyui76543edfghjjbdrtyuio0(*&^787654345678',
        'iv' => '24499350',
        'lifetime' => 86400
    ]
];
