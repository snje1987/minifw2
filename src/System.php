<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class System {

    const MAX_ERROR = 100;

    /**
     * @var static the instance
     */
    protected static $_instance = null;

    public static function get($args = []) {
        if (self::$_instance === null) {
            self::$_instance = new static($args);
        }
        return self::$_instance;
    }

    public static function get_new($args = []) {
        if (self::$_instance !== null) {
            self::$_instance = null;
        }
        return self::get($args);
    }

    protected $_calls = [];

    /**
     * @var Org\Snje\Minifw\Config
     */
    protected $config;
    protected $errors = [];
    protected $use_buffer = false;
    protected $log_error = false;
    protected $is_cli = false;

    protected function __construct($cfg_path) {

        if (PHP_SAPI === 'cli') {
            $this->is_cli = true;
        }

        $_GET = self::magic_gpc($_GET);
        $_POST = self::magic_gpc($_POST);
        $_COOKIE = self::magic_gpc($_COOKIE);

        if (!file_exists($cfg_path)) {
            die('配置文件不存在');
        }
        $this->config = Config::get_new($cfg_path);
        if (!defined('WEB_ROOT')) {
            $web_root = $this->config->get_config('path', 'web_root', '');
            $web_root = rtrim(str_replace('\\', '/', $web_root));
            if ($web_root == '') {
                die('未指定WEB_ROOT.');
            }
            define('WEB_ROOT', $web_root);
        }
        if (!defined('DEBUG')) {
            define('DEBUG', $this->config->get_config('debug', 'debug', 0));
        }
        if (!defined('DBPREFIX')) {
            define('DBPREFIX', $this->config->get_config('main', 'dbprefix', ''));
        }
        date_default_timezone_set($this->config->get_config('main', 'timezone', 'UTC'));

        $this->log_error = $this->config->get_config('debug', 'log_error', 0);

        //设置错误处理函数
        set_error_handler([$this, 'captureNormal']);
        //设置异常处理函数
        set_exception_handler([$this, 'captureException']);
        //设置停机处理函数
        register_shutdown_function([$this, 'captureShutdown']);
        if (!headers_sent()) {
            header('Content-type:text/html;charset=' . $this->config->get_config('main', 'encoding', 'utf-8'));
        }
    }

    public function run() {
        $path = $this->config->get_config('main', 'uri', '/');
        try {
            foreach ($this->_calls as $v) {
                $matches = [];
                if (preg_match($v['reg'], $path, $matches) === 1) {
                    if (!isset($v['option']['session']) || $v['option']['session']) {
                        $this->_set_seesion();
                    }
                    if (!isset($v['option']['buffer']) || $v['option']['buffer']) {
                        ob_start();
                        $this->use_buffer = true;
                    }
                    array_shift($matches);
                    call_user_func_array($v['callback'], $matches);
                    if ($this->use_buffer) {
                        if (DEBUG === 1 && !empty($this->errors)) {
                            $content = ob_get_clean();
                            print_r($this->errors);
                            echo $content;
                        }
                        else {
                            @ob_end_flush();
                        }
                    }
                    else {
                        if (DEBUG === 1 && !empty($this->errors)) {
                            print_r($this->errors);
                        }
                    }
                    return;
                }
            }
        }
        catch (\Exception $ex) {
            if ($this->use_buffer) {
                @ob_end_clean();
            }
            $controler = new Controler();
            if (DEBUG === 1) {
                return $controler->show_msg($ex->getMessage(), 'Error');
            }
            else {
                return $controler->show_404();
            }
        }
        if ($this->use_buffer) {
            @ob_end_clean();
        }
        $controler = new Controler();
        if (DEBUG === 1) {
            return $controler->show_msg('路由未指定.', 'Error');
        }
        else {
            return $controler->show_404();
        }
    }

    public function reg_call($reg, $callback, $option = []) {
        $this->_calls[] = [
            'reg' => $reg,
            'callback' => $callback,
            'option' => $option,
        ];
    }

    protected function _set_seesion() {
        $session_name = $this->config->get_config('main', 'session', 'PHPSESSION');
        session_name($session_name);
        session_set_cookie_params(36000, '/', $this->config->get_config('main', 'domain', ''));

        //处理Flash丢失cookie的问题
        $session_id = '';
        isset($_POST[$session_name]) && $session_id = strval($_POST[$session_name]);
        if ($session_id == '') {
            isset($_GET[$session_name]) && $session_id = strval($_GET[$session_name]);
        }
        if ($session_id != '') {
            session_id($session_id);
        }
        session_start();
    }

    /**
     * 处理用户发送的数据，执行trim和去除多余的转义
     * 这里要求php版本>=5.6，已经移除的自动转义功能，所以不用再处理转义符
     *
     * @param mixed $string 要处理的数据
     * @return mixed 处理后的数据
     */
    public static function magic_gpc($string) {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = self::magic_gpc($val);
            }
        }
        else {
            $string = trim($string);
        }
        return $string;
    }

    public function captureNormal($number, $message, $file, $line) {
        if (DEBUG === 1) {
            if (!$this->is_cli && count($this->errors) < self::MAX_ERROR) {
                $this->errors[] = ['type' => $number, 'message' => $message, 'file' => $file, 'line' => $line];
            }
            else {
                echo '[' . $number . '] ' . $file . '[' . $line . ']:' . $message . "\n";
            }
        }
        if ($this->log_error) {
            error_log('[' . $number . '] ' . $file . '[' . $line . ']:' . $message);
        }
    }

    /**
     * @param \Exception $exception
     */
    public function captureException($exception) {
        if ($this->use_buffer) {
            @ob_end_clean();
        }
        if (DEBUG === 1) {
            header('Content-type:text/plain;charset=' . $this->config->get_config('main', 'encoding', 'utf-8'));
            print_r($exception);
        }
        else {
            echo 'Runtime Error';
        }
        if ($this->log_error) {
            error_log('[' . $exception->getCode() . '] ' . $exception->getFile() . '[' . $exception->getLine() . ']:' . $exception->getMessage());
        }
    }

    public function captureShutdown() {
        $error = error_get_last();
        if ($error !== null) {
            if ($this->use_buffer) {
                @ob_end_clean();
            }
            if (DEBUG === 1) {
                if (!headers_sent()) {
                    header('Content-type:text/plain;charset=' . $this->config->get_config('main', 'encoding', 'utf-8'));
                }
                print_r($error);
            }
            else {
                echo 'Runtime Error';
            }
            if ($this->log_error) {
                error_log('[' . $error['type'] . '] ' . $error['file'] . '[' . $error['line'] . ']:' . $error['message']);
            }
        }
        else {
            return true;
        }
    }

}
