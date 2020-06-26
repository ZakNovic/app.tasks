<?php

use AppTask\Controllers\ListController;
use AppTask\ErrorHelper;
use AppTask\FileSystem;

$root = dirname(__FILE__, 2);
//autoloader and PDO object
require_once ($root . '/bootstrap.php'); 
//error handler
$errorHelper = new ErrorHelper(FileSystem::append([$root, 'templates'])); 
try { 
    $controller = new ListController($root, $pdo);
    //processing get parameters
    $controller->get('taskAdded', function ($key, $value, ListController $c) {
        $c->addMessage('Задача успешно добавлена...');
    });
    $controller->start();
    
} catch (\Throwable $e) {
    $errorHelper->dispatch($e);
}