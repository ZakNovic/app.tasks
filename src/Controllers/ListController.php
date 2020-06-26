<?php
namespace AppTask\Controllers;

use AppTask\Database\TaskMapper;
use AppTask\Database\UserMapper;
use AppTask\FileSystem;
use AppTask\Input\SearchQueryValidator;
use AppTask\LoginManager;
use AppTask\Pager;
use AppTask\SearchData;
use AppTask\Views\ListView;

class ListController extends AppController
{
    private $root;
    private $pdo;
    
    function __construct($root, $pdo)
    {
        parent::__construct();
        $this->root = $root;
        $this->pdo = $pdo;
    }
    
    function start()
    {
        $this->execute();
        $this->listPage($this->root, $this->pdo);
    }
    
    protected function listPage($root, \PDO $pdo)
    {
        $mapper    = new TaskMapper($pdo);
        $validator = new SearchQueryValidator();
        $pager     = new Pager();
        $messages  = array();
        
        //initialize the mappers for the database
        $userMapper = new UserMapper($pdo);
        $loginMan  = new LoginManager($userMapper, $pdo);
        //check user login (if any)
        $authorized = $loginMan->isLogged();
        //admin check
        $isAdmin = $loginMan->isAdmin();
        //if the user is login - save the name
        if ($authorized === true) {
            $usernameDisplayed = $loginMan->getLoggedName();
        } else {
            $usernameDisplayed = '';
        }
        
        //get data for db query
        $searchData = $validator->genSearchData($_GET);

        try {
            $pdo->beginTransaction();
            $tasks = $mapper->getTasks($searchData);
        } catch (\Throwable $e) {
            //if an error - roll back and pass up
            $pdo->rollBack();
            throw new \Exception('Ошибка во время получения задач из базы данных', 0, $e);
        }
        $entriesCount = $mapper->getEntriesCount();
        //total number of results found for the last search query
        $queries = $pager->getQueries($_GET, $entriesCount);
        $view = new ListView( FileSystem::append([$this->root, 'templates']) );
        $view->render([
            'tasks'    => $tasks,
            'messages' => $this->messages,
            'queries'  => $queries,
            'authorized' => $authorized,
            'is_admin' => $isAdmin,
            'username'   => $usernameDisplayed
        ]);
    }
}