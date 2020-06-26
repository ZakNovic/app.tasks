<?php
namespace AppTask;


use AppTask\Database\LoginMapper;
use AppTask\Database\UserMapper;

class LoginManager
{

    private $pdo;
    private $mapper;
    private $input;
    /**
     * @var bool VERY important variable, which tells outside world about,
     * whether 'user' is logged OR it's some stranger.
     */
    private $islogged = false;
    /**
     * @var int Holder for id of user, if exists.
     */
    private $id = 0;
    
    /**
     * LoginManager constructor.
     * @param UserMapper $mapper Mapper that can store hashes in DB.
     * @param array $inputArray Input, containing 'password' and 'user data' of current user of site.
     */
    function __construct(UserMapper $mapper, $pdo)
    {
        $this->mapper = $mapper;
        $this->pdo = $pdo;
    }
    
    /**
     * This method adds User to database.
     *
     * Be careful, this method cannot check whether ID is valid and non-duplicate, so do it yourself.
     * @param string $username
     * @param string $password
     */
    function registerUser($username, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = $this->mapper->addUser($username, $hash);
        return $user;
    }
    
    /**
     * Checks depending on provided input, whether user has valid credentials
     * and therefore may be provided with access to system.
     *
     * It also sets internal state of LoginManager to 'logged' or 'not logged'.
     * @return int|bool ID of user if he has valid credentials, or FALSE if not.
     */
    function checkLoginForm($input)
    {
        //check the availability of data for login
        if  (array_key_exists('login_form_sent', $input)
            AND
             array_key_exists('navbar_username', $input)
            AND
             array_key_exists('navbar_pwd', $input)
        ) {
            //filter data
            $password = (string)$input['navbar_pwd'];
            $name = (string)$input['navbar_username'];
            $hash = $this->mapper->getHashByName($name);
            //check the received hash (if an error, false instead)
            if ($hash !== false) {
                //match check
                if (password_verify($password, $hash)) {
                    //if the user entered the correct data, we get his ID and return
                    $result = $this->mapper->getUserByName($name)->getId();
                } else {
                    $result = false;
                }
                //check if the standard hash method in php has been updated
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $this->mapper->changeHashForUser($name, $hash);
                }
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        
        return $result;
        
    }
    
    /**
     * @param $userid
     */
    function persistLogin($userid)
    {
        $loginMapper = new LoginMapper($this->pdo);
        //random cookie token
        $token = self::genRandString(24);
        //его хеш для хранения в бд
        $tokenHash = hash('sha256', $token);
        //user identifier will be ID of login entry in db
        $id = $loginMapper->addLogin($tokenHash, $userid);
        //if record in db was successful, write login information in cookies
        if ($id != false) {
            setcookie('login_id', $id, time()+60*60*24, null, null, null, true);
            setcookie('token', $token,   time()+60*60*24, null, null, null, true);
        }
    }
    
    function logout()
    {
        setcookie('login_id', 0, time()-60*60*24, null, null, null, true);
        setcookie('token', 0,   time()-60*60*24, null, null, null, true);
    }
    
    /**
     * By default it returns FALSE.
     * @return bool TRUE if user is valid and logged, otherwise FALSE.
     */
    function isLogged()
    {
        //check the availability of data in cookies
        if  (array_key_exists('login_id', $_COOKIE) AND is_string($_COOKIE['login_id'])
            AND
            array_key_exists('token', $_COOKIE) AND is_string($_COOKIE['token'])
        ) {
            $loginID = (int)$_COOKIE['login_id'];
            $token = (string)$_COOKIE['token'];
            $loginMapper = new LoginMapper($this->pdo);
            //check for id (series) of the token in db
            $hash = $loginMapper->getHash($loginID);
            //do something only if hash is found in db
            if ($hash != false) {
                //if hashes from db and cookies match
                if (hash_equals($hash, hash('sha256', $token))) {
                    //user has necessary data - he is logged in
                    $this->islogged = true;
                } else {
                    //the user gave the desired ID, but failed the password check => theft
                    $this->islogged = false;
                }
                
            } else $this->islogged = false;
            
        } else $this->islogged = false;
        
        return $this->islogged;
    }
    
    /**
     * @return bool
     */
    function isAdmin()
    {
        $result = false;
        if ( $this->isLogged() ) {
            //if logged in, then cookie has an ID
            $loginID = $_COOKIE['login_id'];
            //we call mappers for access to a DB
            $loginMapper = new LoginMapper($this->pdo);
            $userMapper = $this->mapper;
            //get from record about login user id
            $userid = $loginMapper->getUserID($loginID);
            //get username
            $username = $userMapper->getUser($userid)->getName();
            //compare
            if ($username === 'admin') {
                $result = true;
            } else $result = false;
        } else $result = false;
        
        return $result;
    }
    
    /**
     * @return int ID of user, if it's credentials were checked, otherwise false.
     */
    function getLoggedID()
    {
        $userid = false;
        if ( $this->isLogged() ) {
            //if logged in, then cookie has an ID
            $loginID = $_COOKIE['login_id'];
            //we call mappers for access to a DB
            $loginMapper = new LoginMapper($this->pdo);
            $userMapper = $this->mapper;
            //get from record about login user id
            $userid = $loginMapper->getUserID($loginID);
        }
        return $userid;
    }
    
    /**
     * False, если юзер не залогинен
     * @return bool|string
     */
    function getLoggedName()
    {
        $username = false;
        if ( $this->isLogged() ) {
            //if logged in, then cookie has an ID
            $loginID = $_COOKIE['login_id'];
            //we call mappers for access to a DB
            $loginMapper = new LoginMapper($this->pdo);
            $userMapper = $this->mapper;
            //get from record about login user id
            $userid = $loginMapper->getUserID($loginID);
            //get username
            $username = $userMapper->getUser($userid)->getName();
        }
        return $username;
    }
    
    /**
     * Generates cryptographically secure string of given length.
     * @param int $length Length of desired random string.
     * @param string $chars Only these characters may be included into string.
     * @return string
     * @throws \Exception
     */
    private static function genRandString($length, $chars='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/')
    {
        if (!is_string($chars) || strlen($chars) == 0) {
            throw new \Exception('Parameter is not string or is empty');
        }
        
        $str = '';
        $keysize = strlen($chars) -1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $chars[random_int(0, $keysize)];
        }
        return $str;
    }
}