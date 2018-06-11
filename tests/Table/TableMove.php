<?php

namespace Org\Snje\MinifwTest\Table;

use Org\Snje\Minifw as FW;

class TableMove extends FW\Table {

    public static $tbname = 'table_with_all';

    protected function _prase($post, $odata = []) {

    }

    public static $status = [
        'engine' => 'InnoDB',
        'charset' => 'utf8',
        'comment' => 'Table To Create',
    ];
    public static $field = [
        'charfield_def' => ['type' => 'varchar(200)', 'default' => '', 'comment' => 'A varchar field'],
        'intfield_def' => ['type' => 'int(11)', 'default' => '0', 'comment' => 'A int field'],
        'textfield' => ['type' => 'text', 'comment' => 'A text field'],
        'charfield' => ['type' => 'varchar(200)', 'comment' => 'A varchar field'],
        'intfield' => ['type' => 'int(11)', 'comment' => 'A int field'],
        'id' => ['type' => 'int(10) unsigned', 'extra' => 'auto_increment', 'comment' => 'ID'],
    ];
    public static $index = [
        'PRIMARY' => ['fields' => ['id'], 'comment' => '主键'],
        'charfield' => ['fields' => ['charfield']],
        'intfield' => ['fields' => ['intfield', 'charfield']],
        'uniqueindex' => ['unique' => true, 'fields' => ['intfield']]
    ];
    public static $diff = [
        [
            'diff' => '-[5] `charfield_def` varchar(200) NOT NULL DEFAULT \'\' COMMENT \'A varchar field\'' . "\n" . '+[0] `charfield_def` varchar(200) NOT NULL DEFAULT \'\' COMMENT \'A varchar field\'',
            'trans' => 'ALTER TABLE `table_with_all` CHANGE `charfield_def` `charfield_def` varchar(200) NOT NULL DEFAULT \'\' COMMENT \'A varchar field\' first;',
        ],
        [
            'diff' => '-[5] `intfield_def` int(11) NOT NULL DEFAULT \'0\' COMMENT \'A int field\'' . "\n" . '+[1] `intfield_def` int(11) NOT NULL DEFAULT \'0\' COMMENT \'A int field\'',
            'trans' => 'ALTER TABLE `table_with_all` CHANGE `intfield_def` `intfield_def` int(11) NOT NULL DEFAULT \'0\' COMMENT \'A int field\' after `charfield_def`;',
        ],
        [
            'diff' => '-[5] `textfield` text NOT NULL COMMENT \'A text field\'' . "\n" . '+[2] `textfield` text NOT NULL COMMENT \'A text field\'',
            'trans' => 'ALTER TABLE `table_with_all` CHANGE `textfield` `textfield` text NOT NULL COMMENT \'A text field\' after `intfield_def`;',
        ],
        [
            'diff' => '-[5] `charfield` varchar(200) NOT NULL COMMENT \'A varchar field\'' . "\n" . '+[3] `charfield` varchar(200) NOT NULL COMMENT \'A varchar field\'',
            'trans' => 'ALTER TABLE `table_with_all` CHANGE `charfield` `charfield` varchar(200) NOT NULL COMMENT \'A varchar field\' after `textfield`;',
        ],
        [
            'diff' => '-[5] `intfield` int(11) NOT NULL COMMENT \'A int field\'' . "\n" . '+[4] `intfield` int(11) NOT NULL COMMENT \'A int field\'',
            'trans' => 'ALTER TABLE `table_with_all` CHANGE `intfield` `intfield` int(11) NOT NULL COMMENT \'A int field\' after `charfield`;',
        ],
    ];

}
