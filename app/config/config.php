<?php

return [
    'aliases' => [
        '@app' => __DIR__.'/..',
    ],
    'components' => [
        'db' => [
            "params" => [
                "class" => "app\\core\\db\\Connection",
            ],
            'config' => [
                'dsn' => 'mysql:host=localhost;dbname=sms_temp',
                'username' => 'root',
                'password' => 'platinumhorse39',
            ],
        ],
        'log' => [
            'params' => [
                "class" => "app\\components\\LogFactory",
            ],
            'config' => [
                'targetClass' => 'app\\components\\FileTarget',
                'logPath' => 'logs/',
                'logFileName' => 'app_log.txt',
            ]
        ],
    ]
];
