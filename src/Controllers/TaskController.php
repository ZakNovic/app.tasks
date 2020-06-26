<?php
/**
 * Created by PhpStorm.
 * User: Shinoa
 * Date: 07.08.2017
 * Time: 19:11
 */

namespace AppTask\Controllers;


use AppTask\Database\TaskMapper;
use AppTask\Database\UserMapper;
use AppTask\FileSystem;
use AppTask\Input\ImageLoaderBase64;
use AppTask\Input\NewTaskValidator;
use AppTask\LoginManager;
use AppTask\Views\TaskView;

class TaskController extends AppController
{
    private $root;
    private $public;
    private $pdo;
    private $errors;
    
    function __construct($root, $public, $pdo)
    {
        parent::__construct();
        $this->root = $root;
        $this->public = $public;
        $this->pdo = $pdo;
    }
    
    function start()
    {
        $this->execute();
        $this->regPage($this->root, $this->public, $this->pdo);
    }
    
    protected function regPage($root, $public, \PDO $pdo)
    {
        $userMapper = new UserMapper($pdo);
        $taskMapper = new TaskMapper($pdo);
        $validator = new NewTaskValidator();
        $loginMan  = new LoginManager($userMapper, $pdo);
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
                //if the user is authorized, we use his account, otherwise the Guest account
                $taskUsername = $authorized ? $usernameDisplayed : 'Guest';
                $userID = $userMapper->getIdFromName($taskUsername);
                //добавляем запись с расчитанными и проверенными параметрами
                $taskMapper->addTask($userMapper, $userID, $data['email'], $data['task_text']);
                $this->redirect('list.php?taskAdded');
            } else {
                $dataBack['email'] = $_POST['email'];
                $dataBack['task_text'] = $_POST['task_text'];
            }
        }
        
        $view = new TaskView(FileSystem::append([$root, '/templates']));
        $view->render([
            'errors'     => $this->errors,
            'messages'   => $this->messages,
            'databack'   => $dataBack,
            'authorized' => $authorized,
            'username'   => $usernameDisplayed
        ]);
    }
}