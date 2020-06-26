<?php

use AppTask\Controllers\TaskController;
use AppTask\ErrorHelper;
use AppTask\FileSystem;

$root = dirname(__FILE__, 2);
$public = __DIR__;
//autoloader and PDO object
require_once ($root . '/bootstrap.php');
//error handler
$errorHelper = new ErrorHelper(FileSystem::append([$root, 'templates']));
try {
    $controller = new TaskController($root, $public, $pdo);
    $controller->start();
} catch (\Throwable $e) {
    $errorHelper->dispatch($e);
}