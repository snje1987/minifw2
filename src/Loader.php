<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;

class Loader {

    public static $len;

    /**
     * 注册加载函数
     */
    public static function register() {
        self::$len = strlen(__NAMESPACE__ . '\\');
        //设置类的加载器
        spl_autoload_register([__NAMESPACE__ . '\Loader', 'class_loader']);
    }

    /**
     * 加载指定的类
     *
     * @param string $name 要加载的类的完全限定名
     * @return bool 成功返回true，否则返回false
     */
    public static function class_loader($name) {
        if (strncmp(__NAMESPACE__ . '\\', $name, self::$len) !== 0) {
            return false;
        }
        $name = substr($name, self::$len);
        $file_path = __DIR__ . '/' . str_replace('\\', '/', $name) . '.php';
        if (file_exists($file_path) && is_readable($file_path)) {
            include($file_path);
            return true;
        }
        return false;
    }

}

FW\Loader::register();
