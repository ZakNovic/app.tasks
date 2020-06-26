<?php
namespace AppTask\Controllers;


use AppTask\Database\TaskMapper;
use AppTask\Database\UserMapper;
use AppTask\FileSystem;
use AppTask\Input\NewTaskValidator;
use AppTask\LoginManager;
use AppTask\Views\EditView;

class EditController extends AppController
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
        //to edit task
        $taskmapper = new TaskMapper($this->pdo);
        //check if the user has edit rights
        $userMapper = new UserMapper($this->pdo);
        $loginMan  = new LoginManager($userMapper, $this->pdo);
        //check user login (if any)
        $authorized = $loginMan->isLogged();
        //admin check
        $isAdmin = $loginMan->isAdmin();
        if ($authorized AND $isAdmin) {
            $statusChanged = $this->checkAndChangeStatus($_POST, $taskmapper);
            //check the presence of id in the input, without it - an exception
            $taskID = $this->checkTaskID($_POST);
            $editResult = $this->checkAndChangeNewText($_POST, $taskmapper);
            //if you have successfully edited the text -> go back to the main
            if ($editResult) {
                $this->redirect('list.php');
            } else {
                //if not, show the edit window
                $taskText = $taskmapper->getTask($taskID)->getText();
                $view = new EditView(FileSystem::append([$this->root, 'templates']));
                $view->render([
                    'authorized' => $authorized,
                    'task_id' => $taskID,
                    'task_text' => $taskText
                ]);
            }
        }
    }
    
    function checkAndChangeStatus($input, TaskMapper $taskmapper)
    {
        $result = false;
        if (array_key_exists('fulfilled', $input) AND $input['fulfilled'] === '1') {
            if (array_key_exists('task_id', $input)) {
                $result = $taskmapper->changeStatus((int)$input['task_id'], true);
            }
        }
        return $result;
    }
    
    function checkTaskID($input)
    {
        if (array_key_exists('task_id', $input)) {
            return (int)$input['task_id'];
        } else throw new \Exception('ID задачи не указан до редактирования.');
    }
    
    function checkAndChangeNewText($input, TaskMapper $taskmapper )
    {
        //if the editing form is submitted with the task ID
        if (array_key_exists('edit_form_sent', $input)
              AND
            $input['edit_form_sent'] === '1'
              AND
            $taskID = $this->checkTaskID($input)
        ) {
            $validator = new NewTaskValidator();
            $newText = $validator->checkTaskText($input);
            $result = $taskmapper->changeText($taskID, $newText);
        } else $result = false;
        return $result;
    }
}