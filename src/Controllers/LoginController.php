<?php
namespace AppTask\Controllers;


use AppTask\Database\UserMapper;
use AppTask\LoginManager;

class LoginController extends AppController
{
    private $root;
    private $pdo;
    private $is_logged;
    function __construct($root, $pdo)
    {
        parent::__construct();
        $this->root = $root;
        $this->pdo = $pdo;
    }
    
    function start()
    {
        $mapper    = new UserMapper($this->pdo);
        $loginMan  = new LoginManager($mapper, $this->pdo);
        $userID = $loginMan->checkLoginForm($_POST);
        if ($userID !== false ) {
            $loginMan->persistLogin($userID);
        }
        //at the end of all actions - redirect to the main page
        $this->redirect('list.php');
    }
    
    function logout()
    {
        $mapper    = new UserMapper($this->pdo);
        $loginMan  = new LoginManager($mapper, $this->pdo);
        $loginMan->logout();
    }
}