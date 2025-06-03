<?php

use \Phalcon\Cache\Backend\File;
use \Phalcon\Cache\Frontend\Data;
use \Phalcon\Db\Adapter\Pdo\Mysql;
use \Phalcon\Di\FactoryDefault;
use \Phalcon\Escaper;
use \Phalcon\Logger\Adapter\File as Logger;
use \Phalcon\Registry;
use \Wisdom\Firewall;
use \Wisdom\Persistent;
use \Wisdom\Tool;

/**
 * file cache
 */
$Data  = new Data([
    'lifetime' => $Config->cache->lifetime,
]);
$Cache = new File($Data, [
    'cacheDir' => $Config->application->cachedir . '/',
]);

/**
 * dependency injector
 * config, registry, cache, logger, db, acl, bearer, persistent, firewall, tool, escaper
 */
$FactoryDefault = new FactoryDefault();
$FactoryDefault->setShared('config', $Config);
$FactoryDefault->setShared('registry', function () {
    return new Registry();
});
$FactoryDefault->setShared('cache', $Cache);
if ($Config->log->handler === 'FILE') {
    $Logger = new Logger(
        $Config->application->logdir . '/' . date('Y-m-d') . '.log'
    );
    $FactoryDefault->setShared('logger', $Logger);
}
$FactoryDefault->setShared('db', function () use ($Config) {
    return new Mysql([
        'host'     => $Config->database->host,
        'username' => $Config->database->username,
        'password' => $Config->database->password,
        'dbname'   => $Config->database->dbname,
        'options'  => [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ],
    ]);
});
$FactoryDefault->setShared('acl', require_once(CONFIGPATH . 'acl.php'));
$FactoryDefault->setShared('bearer', function () use ($Micro) {
    $authorization = $Micro->request->getHeader('Authorization');
    if (!empty($authorization) &&
        preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
        return $matches[1];
    }
    return null;
});
$FactoryDefault->setShared('mypersistent', function () use ($Config, $Cache) {
    return new Persistent($Config, $Cache);
});
$FactoryDefault->setShared('firewall', function () use ($Config, $Micro, $Cache) {
    return new Firewall($Config, $Micro, $Cache);
});
$FactoryDefault->setShared('tool', function () use ($Config) {
    return new Tool($Config);
});
$FactoryDefault->setShared('escaper', function () use ($Config) {
    return new Escaper();
});

return $FactoryDefault;
