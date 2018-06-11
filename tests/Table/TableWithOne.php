<?php

namespace Org\Snje\MinifwTest\Table;

use Org\Snje\Minifw as FW;

class TableWithOne extends FW\Table {

    public static $tbname = 'table_with_one';

    protected function _prase($post, $odata = []) {

    }

    public static $status = [
        'engine' => 'InnoDB',
        'charset' => 'utf8',
        'comment' => 'Table To Create',
    ];
    public static $field = [
        'intfield' => ['type' => 'int(11)', 'comment' => 'A int field'],
    ];
    public static $index = [
    ];

}
