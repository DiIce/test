<?php

namespace Extensions;

class Error
{
    public $errors;
    
    protected static $instance;  
    private function __construct(){}
    private function __clone()    {}
    private function __wakeup()   {} 
    public static function getInstance() { 
        if ( is_null(self::$instance) ) {
            self::$instance = new Error;
        }
        return self::$instance;
    }
    
    public function addError($error)
    {
        $this->errors[] = $error;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function isError()
    {
        return count($this->errors);
    }        
    
}

?>
