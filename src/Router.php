<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class Router {

    public static function multi_layer_info($url) {
        $url = strval($url);
        $index = strpos($url, '?');
        if ($index !== false) {
            $url = substr($url, 0, $index);
        }

        $matches = [];
        if (preg_match('/^(\/[_a-z0-9\/]*)?\/([_a-z\.0-9]*)(-(.*))?$/', $url, $matches) == 0) {
            throw new Exception('URL不正确.');
        }

        $dir = $matches[1];
        $fname = $matches[2];
        $args = [];
        if (isset($matches[4])) {
            $args = explode('-', $matches[4]);
        }
        else {
            $matches[4] = '';
        }

        return [$dir, $fname, $args];
    }

    /**
     * 预定义路由函数，使用多层级控制器，路径最后一个‘/’前为类名，后面为函数名，之后的以‘-’为分隔符，转化为一个数组参数
     * @param string $url Url
     * @param string $namespace 处理器所属的名空间.
     * @param string $default_controler 默认的处理器
     */
    public function multi_layer_route($url, $namespace, $default_controler) {
        $this->route($url, $namespace, $default_controler, __CLASS__ . '::multi_layer_info');
        return;
    }

    public static function single_layer_info($url) {
        $url = strval($url);
        $index = strpos($url, '?');
        if ($index !== false) {
            $url = substr($url, 0, $index);
        }

        $matches = [];
        if (preg_match('/^(\/[_a-z0-9]*)?\/([_a-z0-9]*)(.*)$/', $url, $matches) == 0) {
            throw new Exception('URL不正确.');
        }

        $classname = isset($matches[1]) ? $matches[1] : '';
        $function = isset($matches[2]) ? $matches[2] : '';
        $args = isset($matches[3]) ? $matches[3] : '';

        return [$classname, $function, $args];
    }

    /**
     * 预定义路由函数，使用单层级控制器，路径第二个‘/’前为类名，后面为函数名，之后的整体作为一个参数
     * @param string $url Url
     * @param string $namespace 处理器所属的名空间.
     * @param string $default_controler 默认的处理器
     */
    public function single_layer_route($url, $namespace, $default_controler) {
        $this->route($url, $namespace, $default_controler, __CLASS__ . '::single_layer_info');
        return;
    }

    protected function route($url, $namespace, $default_controler, $info_func) {
        try {
            list($classname, $funcname, $args) = call_user_func($info_func, $url);

            $classname = str_replace('/', '\\', $classname);
            if ($classname == '') {
                $classname = '\\' . $default_controler;
            }
            if ($classname == '') {
                throw new Exception('未指定Controler.');
            }
            $classname = $namespace . ucwords($classname, '\\');
            if (!class_exists($classname)) {
                throw new Exception('Controler ' . $classname . '不存在.');
            }
            $controler = new $classname();
            if (!$controler instanceof Controler) {
                throw new Exception($classname . '不是一个Controler对象.');
            }
            $controler->dispatch($funcname, $args);
        }
        catch (\Exception $ex) {
            $classname = $namespace . ucwords('\\' . $default_controler, '\\');
            if (!class_exists($classname)) {
                throw new Exception('Controler ' . $classname . '不存在.');
            }
            $controler = new $classname();
            if (!$controler instanceof Controler) {
                throw new Exception($classname . '不是一个Controler对象.');
            }
            $controler->show_404();
        }
    }

    public function resource_route($url, $base) {
        $path = $base . $url;
        $resource_obj = new Resource();
        if (!$resource_obj->compile($path)) {
            return;
        }
        $controler = new Controler();
        if (file_exists(WEB_ROOT . $path)) {
            $controler->readfile_with_304(WEB_ROOT . $path);
            return;
        }
        $controler->show_404();
        return;
    }

}
