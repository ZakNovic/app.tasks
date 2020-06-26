<?php

use AppTask\Controllers\EditController;
use AppTask\ErrorHelper;
use AppTask\FileSystem;

$root = dirname(__FILE__, 2);
//autoloader and PDO object
require_once ($root . '/bootstrap.php');
//error handler
$errorHelper = new ErrorHelper(FileSystem::append([$root, 'templates']));
try {
    $controller = new EditController($root, $pdo);
    $controller->start();
    
} catch (\Throwable $e) {
    $errorHelper->dispatch($e);
}