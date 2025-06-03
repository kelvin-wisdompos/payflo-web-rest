<?php

use Phalcon\Mvc\Micro\Collection;

$Collection_ = [];

foreach ($Config->rest->acceptVersion_ as $version) {
    $Collection = new Collection();
    $Collection->setHandler('TableorderController', true);
    $Collection->setPrefix('/'.$version.'/tableorder');
    $Collection->get('/{branch:[A-Za-z0-9]+}/t/{tableHash:[A-Za-z0-9]+}/?', 'index'.'_'.$version);
    array_push($Collection_, $Collection);
}

return $Collection_;
