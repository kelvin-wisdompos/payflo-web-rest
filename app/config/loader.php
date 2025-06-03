<?php

use \Phalcon\Loader;

/**
 * autoloader
 * controller, model, library, vendor
 */
$Loader = new Loader();
$Loader->registerDirs([
    $Config->application->controllerdir,
    $Config->application->modeldir,
    $Config->application->librarydir,
    $Config->application->vendordir,
]);
$Loader->register();

return $Loader;
