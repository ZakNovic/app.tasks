<?php
/**
 * Created by PhpStorm.
 * User: Shinoa
 * Date: 07.08.2017
 * Time: 0:49
 */

namespace AppTask\Controllers;


use AppTask\Database\UserMapper;
use AppTask\FileSystem;
use AppTask\Input\RegFormValidator;
use AppTask\LoginManager;
use AppTask\Views\RegView;

class RegController extends AppController
{
    private $root;
    private $pdo;
    private $errors;
    public $userID = 0;
    
    function __construct($root, $pdo)
    {
        parent::__construct();
        $this->root = $root;
        $this->pdo = $pdo;
    }
    
    function start()
    {
        $this->execute();
        $this->regPage($this->root, $this->pdo);
    }
    
    protected function regPage($root, \PDO $pdo)
    {
        $mapper    = new UserMapper($pdo);
        $validator = new RegFormValidator($mapper);
        $loginMan  = new LoginManager($mapper, $pdo);
        //check user login (if any)
        $authorized = $loginMan->isLogged();
        //if the user is login - save the name
        if ($authorized === true) {
            $usernameDisplayed = $loginMan->getLoggedName();
        } else {
            $usernameDisplayed = '';
        }
        $dataBack  = array();  //invalid input values
        //form submission verification
        if ($validator->dataSent($_POST)) {
            //check validate
            $data = $validator->checkInput($_POST, $this->errors);
            if ($data !== false) {
                $user = $loginMan->registerUser($data['username'], $data['password']);
                $this->redirect('registration.php?registered');
            } else {
                $dataBack['username'] = $_POST['username'];
            }
        }
        $view = new RegView(FileSystem::append([$root, '/templates']));
        $view->render([
            'errors'     => $this->errors,
            'messages'   => $this->messages,
            'databack'   => $dataBack,
            'authorized' => $authorized,
            'username'   => $usernameDisplayed
        ]);
    }
}