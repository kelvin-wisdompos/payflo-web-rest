<?php

use Phalcon\Acl;
use Phalcon\Acl\Adapter\Memory;
use Phalcon\Acl\Resource;
use Phalcon\Acl\Role;

$Memory = new Memory();
$Memory->setDefaultAction(Acl::DENY);
$Memory->addRole(new Role('BACKDOOR'));
$Memory->addRole(new Role('CRON'));
$Memory->addRole(new Role('GUEST'));
$Memory->addRole(new Role('LIMITED'));
$Memory->addRole(new Role('STAFF'));
$Memory->addRole(new Role('MANAGER'));
$Memory->addRole(new Role('OWNER'));
$Memory->addRole(new Role('ADMIN'));
$Memory->addRole(new Role('VOID'));
$Memory->addRole(new Role('LOCAL_POS_REST'));
$Memory->addInherit('CRON', 'GUEST');
$Memory->addInherit('LIMITED', 'GUEST');
$Memory->addInherit('MANAGER', 'GUEST');
$Memory->addInherit('STAFF', 'LIMITED');
$Memory->addInherit('OWNER', 'STAFF');
$Memory->addInherit('OWNER', 'MANAGER');
$Memory->addInherit('ADMIN', 'OWNER');
$Memory->addInherit('LOCAL_POS_REST', 'OWNER');

$Resource_ = [
    'BACKDOOR' => [
        'Tableorder' => [
            'index',
        ],
    ],



    'GUEST' => [
    ],



    'LIMITED' => [
       
    ],



    'STAFF' => [
    ],



    'MANAGER' => [
    ],



    'OWNER' => [
    ],



    'ADMIN' => [
    ],



    'CRON' => [
    ],



    'VOID' => [
    ],



    'LOCAL_POS_REST' => [
    ],
];

foreach ($Resource_ as $Resource) {
    foreach ($Resource as $controller => $method_) {
        foreach ($Config->rest->acceptVersion_ as $version) {
            foreach ($method_ as $l => $method) {
                $method_[$l] = $method.'_'.$version;
            }
            $Memory->addResource(new Resource($controller), $method_);
        }
    }
}
foreach ($Memory->getRoles() as $Role) {
    foreach ($Resource_[$Role->getName()] as $controller => $method_) {
        foreach ($Config->rest->acceptVersion_ as $version) {
            foreach ($method_ as $l => $method) {
                $method_[$l] = $method.'_'.$version;
            }
            $Memory->allow($Role->getName(), $controller, $method_);
        }
    }
}

return $Memory;
