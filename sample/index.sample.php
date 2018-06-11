<?php

namespace Site;

use Org\Snje\Minifw as FW;

require_once '../vendor/autoload.php';
$app = FW\System::get(dirname(__DIR__) . '/config.php');
$app->reg_call('/^(.*)$/', function($path) {
    $router = new FW\Router();
    $router->multi_layer_route($path, 'Site\\Controler', 'Default');
});
$app->run();
