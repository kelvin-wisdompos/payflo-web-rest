<?php

$config = [
    'timelimit' => 30,
    'timezone'  => 'UTC',
    'status'    => 'development',
    'database'  => [
        'host'     => '192.168.2.3',
        'username' => 'wisdompos',
        'password' => 'wisdompos101',
        'dbname'   => 'wisdompos',
        'timezone' => 'UTC',
    ],
    'application' => [
        'servername'    => 'http://192.168.2.202:8080/wisdomposrest',
        'controllerdir' => 'app/controller',
        'modeldir'      => 'app/model',
        'librarydir'    => 'app/library',
        'vendordir'     => 'vendor',
        'cachedir'      => 'cache',
        'logdir'        => 'log',
        'imagedir'      => 'static/companyId/images',
        'supportdir'    => 'static/support',
        'onlineorderdir'=> '/var/www/html/wisdompos-onlineorder',
        'web'           => 'http://localhost:8080/wisdompos-onlineorder',
    ],
    'rest' => [
        'acceptVersion_' => ['v1'],
        'statuskey'      => 'status',
        'messagekey'     => 'message',
        'reloginkey'     => 'relogin',
        'resultkey'      => 'result',
        'ordinalkey'     => 'ordinal',
        'statusok'       => 'OK',
        'statuserror'    => 'ERROR',
    ],
    'JWT' => [
        'alogaritm'             => 'RS512',
        'accessnbf'             => 0,
        'developmentaccessnbf'  => 0,
        'accessexp'             => 3600,
        'developmentaccessexp'  => 1 * 3600, //sehari
        'refreshnbf'            => 0,
        'developmentrefreshnbf' => 0,
        'refreshexp'            => 3600 * 24 * 7,
        'developmentrefreshexp' => 3600 * 24 * 7,
        'privatekey'            => require_once('key/rs512private.php'),
        'developmentprivatekey' => require_once('key/developmentrs512private.php'),
        'publickey'             => require_once('key/rs512public.php'),
        'developmentpublickey'  => require_once('key/developmentrs512public.php'),
    ],
    'cache' => [
        'lifetime'    => 3600,
        'datakey'     => 'data-',
        'firewallkey' => 'firewall-',
    ],
    'firewall' => [
        'lifetime'  => 600,
        'increment' => 1,
        'cap'       => 10,
        'code'      => [400, 401],
    ],
    'log' => [
        'exclude' => [404, 423],
        'handler' => 'DB',
    ],
    'regex' => [
        'phone' => [
            'AU' => '/^04[0-9]{8}$|^02[0-9]{8}$|^03[0-9]{8}$|^07[0-9]{8}$|^08[0-9]{8}$/',
            'NZ' => '/^02[0-9]{7,9}$|^03[0-9]{7}$|^04[0-9]{7}$|^06[0-9]{7}$|^07[0-9]{7}$|^09[0-9]{7}$/',
            'ID' => '/^08[0-9]{8,11}$|^(?!08)[0-9]{7,11}$/',
            'PG' => '/^[0-9]{7,8}$/',
        ],
        'mobile' => [
            'AU' => '/^04[0-9]{8}$/',
            'NZ' => '/^02[0-9]{7,9}$/',
            'ID' => '/^08[0-9]{8,11}$/',
            'PG' => '/^[0-9]{7,8}$/',
        ],
        'clock' => '/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/',
        'email' => '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i',
        'day'   => '/^(mon|tue|wed|thu|fri|sat|sun)$/i',
    ],
    'admin' => [
        'email'    => 'support@wisdombusinesssolutions.com.au',
        'backdoor' => 'SRsQnLsqaGm26YCc' . date('Y-m-d'),
    ],
    'salt' => 'Wisdompos',
    'linklycloud' => [
        'development' => 'https://auth.sandbox.cloud.pceftpos.com/v1/',
        'production' => 'https://auth.cloud.pceftpos.com/v1/',
    ],
    'wisdompay' => [
        'apiKey' => '',
        'environment' => 'test', //test or live
        'merchant' => '', 
    ],
    'adyen' => [
        'taptopay' => [
            'apiKey' => 'AQErhmfxLIzLbRBKw0m/n3Q5qf3Vf4JeCZxHaEplU3L4f7N+OZ3XD+1Isx9YyRDBXVsNvuR83LVYjEgiTGAH-0zh1itwyD+lqqjRGhD380ok6p7V2RXoObdGJ7B0rb1M=-i1ivXIe^eH4JEk,{vka',
            'environment' => 'test', //test or live
            'merchant' => '', 
        ]
    ],
];

/**
 * set timelimit
 */
set_time_limit($config['timelimit']);

/**
 * set timezone
 */
date_default_timezone_set($config['timezone']);

/**
 * turn error off
 */
if ($config['status'] !== 'development') {
    error_reporting(0);
}

/**
 * set default http status code to 500
 * https://stackoverflow.com/questions/2331582/catch-php-fatal-error
 */
http_response_code(500);

return $config;
