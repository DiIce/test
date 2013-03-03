<?php

namespace Extensions;

use Symfony\Component\Validator\Constraints as Assert;

class User {
    
    public  $info  = NULL;
    public  $auth;    
    
    public function __construct(){
        if(!isset($_SESSION['lang'])) $_SESSION['lang'] = \Extensions\Config::getInstance()->getOption('site', 'lang');
        if(isset($_SESSION['token'])){
            $this->info = \Model\User::find_by_token($_SESSION['token']);
        } elseif(isset($_COOKIES['token'])){
            $this->info = \Model\User::find_by_token($_COOKIES['token']);
        }
        if($this->info !== NULL){
            $this->auth = true;
            $_SESSION['lang'] = $this->info->language;
            $this->newActivity();
            $this->setNewToken();
        } else {
            $this->auth = false; 
        }
    }
    
    public function auth($login,$password){
        $tmp = \Model\User::find_by_login_and_password($login,$password);
        if($tmp->login == $login && $tmp->password == $password){
            $this->info = $tmp;
            $this->setNewToken();
            $this->newActivity();          
            return true;
        } else {
            return false; 
        }
    }
    
    public function setLanguage($lang){
        if($this->auth){
            $this->info->language = $lang;
            $this->info->save();
        }
        $_SESSION['lang'] = $lang;
    }
    
    public function registration($userData,$invite,$avatar){
        global $app;
        $validation = new Assert\Collection(array(
            'login'             => array(new Assert\NotBlank(), new Assert\MinLength(5)),
            'password'          => array(new Assert\NotBlank(), new Assert\MinLength(6)),
            'email'             => array(new Assert\NotBlank(), new Assert\Email()),
            'name'              => array(new Assert\NotBlank(), new Assert\MinLength(5)),
        ));
        $invite = \Model\Invite::find_by_invite($invite);
        $errors = $app['validator']->validateValue($userData, $validation);
        if (count($errors) > 0) {
            foreach($errors as $error)
            {
                $app['errors']->addError($error);
            }    
        } else {
            $userData['password']           = md5($userData['password']);
            $userData['avatar']             = $avatar;
            $userData['registration_date']  = time();
            $this->info = \Model\User::create($userData);
            if($invite !== NULL)
            {
                $invite->referal = $this->info->id;
                $invite->save();
            }    
            $this->auth($userData['login'],$userData['password']);
            return true;
        }
    }
    
    private function newActivity(){
        $this->info->last_visit = time();
        $this->info->save();
    }
    
    private function setNewToken(){
        $this->info->token = $this->generateToken();
        $this->info->save();
        $_SESSION['token'] = $this->info->token;
        setcookie('token', $this->info->token, time()+60*60*24*7);
    }
    
    public function generateToken(){
        return md5(time().rand(1,9999));
    }
    
}

?>
