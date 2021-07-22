<?php

namespace application\core;

define('ROOT', dirname(__DIR__));
define("DEBUG", 1);

class ErrorHandler{
    public function __construct(){
        if(DEBUG){
            error_reporting(-1);
        }else{
            error_reporting(0);
        }
        set_error_handler([$this, 'errorHandler']);
        ob_start();
        register_shutdown_function([$this, 'fatalErrorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    public function errorHandler($errno, $errstr, $errfile, $errline){
        $this->logErrors($errno, $errstr, $errfile, $errline);
        if(DEBUG || in_array($errno, [E_USER_ERROR, E_RECOVERABLE_ERROR])){
            $this->displayError($errno, $errstr, $errfile, $errline);
        }
        return true;
    }

    public function fatalErrorHandler(){
        $error = error_get_last();
        if(!empty($error) and $error['type'] && (E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR)){
            $this->logErrors($error['type'], $error['message'], $error['file'], $error['line']);
            ob_end_clean();
            $this->displayError($error['type'], $error['message'], $error['file'], $error['line']);
        }else{
            ob_end_flush();
        }
    }

    public function exceptionHandler($e){
        $this->logErrors( $e->getCode(), $e->getMessage(),$e->getFile(), $e->getLine());
        $this->displayError('Исключение', $e->getMessage(),$e->getFile(), $e->getLine(), $e->getCode());
    }

    protected function logErrors($code= '',$message='', $file='', $line=''){
        error_log("[".date('Y-m-d H:i:s')."] Код ошибки: {$code} | Текст ошибки: {$message} | Файл: {$file} | Строка: {$line}\n \n", 3 , ROOT.'\tmp\errors.log');

    }

    protected function displayError($errno, $errstr, $errfile, $errline, $response = 500){
        http_response_code($response);
        if($response == 404 && !DEBUG){
            require ROOT. '\views\errors\404.php';
            die;
        }
        if(DEBUG){
            require ROOT.'/views/errors/dev.php';
        }else{
            require ROOT.'/views/errors/prod.php';
        }
        die;
    }
}