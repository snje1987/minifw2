<?php

namespace Org\Snje\MinifwTest\JsonCall;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class Table extends FW\Table {

    public function func($args) {
        return $args;
    }

    public function func_except($args) {
        throw new \Org\Snje\Minifw\Exception($args);
    }

    protected function _prase($post, $odata = []) {

    }

}
