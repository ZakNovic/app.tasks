<?php

use AppTask\Controllers\RegController;
use AppTask\ErrorHelper;
use AppTask\FileSystem;

$root = dirname(__FILE__, 2);
//autoloader and PDO object
require_once ($root . '/bootstrap.php');
//error handler
$errorHelper = new ErrorHelper(FileSystem::append([$root, 'templates']));
try {
    $controller = new RegController($root, $pdo);
    //processing get parameters
    $controller->get('registered', function ($key, $value, RegController $c) {
        $c->addMessage('Поздравляем! Вы успешно зарегистрированы...');
    });
    $controller->start();
    
} catch (\Throwable $e) {
    $errorHelper->dispatch($e);
}