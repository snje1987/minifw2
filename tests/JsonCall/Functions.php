<?php

namespace Org\Snje\MinifwTest\JsonCall;

class Functions {

    public function __construct() {

    }

    public static function static_func($args) {
        return $args;
    }

    public static function static_except($args) {
        throw new \Org\Snje\Minifw\Exception($args);
    }

    public function func($args) {
        return $args;
    }

    public function func_except($args) {
        throw new \Org\Snje\Minifw\Exception($args);
    }

}
