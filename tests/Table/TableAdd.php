<?php

namespace Org\Snje\MinifwTest\Table;

use Org\Snje\Minifw as FW;

class TableAdd extends FW\Table {

    public static $tbname = 'table_with_one';

    protected function _prase($post, $odata = []) {

    }

    public static $status = [
        'engine' => 'InnoDB',
        'charset' => 'utf8',
        'comment' => 'Table To Create',
    ];
    public static $field = [
        'id' => ['type' => 'int(10) unsigned', 'extra' => 'auto_increment', 'comment' => 'ID'],
        'intfield' => ['type' => 'int(11)', 'comment' => 'A int field'],
        'charfield' => ['type' => 'varchar(200)', 'comment' => 'A \' varchar field'],
        'textfield' => ['type' => 'text', 'comment' => 'A text field'],
        'addfield' => ['type' => 'text', 'comment' => 'A add field'],
        'intfield_def' => ['type' => 'int(11)', 'default' => '0', 'comment' => 'A int field'],
        'charfield_def' => ['type' => 'varchar(200)', 'default' => '123\'', 'comment' => 'A varchar field'],
    ];
    public static $index = [
        'PRIMARY' => ['fields' => ['id'], 'comment' => '主\'键'],
        'intfield' => ['fields' => ['intfield', 'charfield']],
        'uniqueindex' => ['unique' => true, 'fields' => ['intfield']],
        'addfield' => ['fields' => ['charfield']],
    ];
    public static $diff = [
        [
            'diff' => '+[0] `id` int(10) unsigned NOT NULL COMMENT \'ID\'',
            'trans' => 'ALTER TABLE `table_with_one` ADD `id` int(10) unsigned NOT NULL COMMENT \'ID\' first;',
        ],
        [
            'diff' => '+[2] `charfield` varchar(200) NOT NULL COMMENT \'A \'\' varchar field\'',
            'trans' => 'ALTER TABLE `table_with_one` ADD `charfield` varchar(200) NOT NULL COMMENT \'A \'\' varchar field\' after `intfield`;',
        ],
        [
            'diff' => '+[3] `textfield` text NOT NULL COMMENT \'A text field\'',
            'trans' => 'ALTER TABLE `table_with_one` ADD `textfield` text NOT NULL COMMENT \'A text field\' after `charfield`;',
        ],
        [
            'diff' => '+[4] `addfield` text NOT NULL COMMENT \'A add field\'',
            'trans' => 'ALTER TABLE `table_with_one` ADD `addfield` text NOT NULL COMMENT \'A add field\' after `textfield`;',
        ],
        [
            'diff' => '+[5] `intfield_def` int(11) NOT NULL DEFAULT \'0\' COMMENT \'A int field\'',
            'trans' => 'ALTER TABLE `table_with_one` ADD `intfield_def` int(11) NOT NULL DEFAULT \'0\' COMMENT \'A int field\' after `addfield`;',
        ],
        [
            'diff' => '+[6] `charfield_def` varchar(200) NOT NULL DEFAULT \'123\'\'\' COMMENT \'A varchar field\'',
            'trans' => 'ALTER TABLE `table_with_one` ADD `charfield_def` varchar(200) NOT NULL DEFAULT \'123\'\'\' COMMENT \'A varchar field\' after `intfield_def`;',
        ],
        [
            'diff' => '+ PRIMARY KEY (`id`) COMMENT \'主\'\'键\'',
            'trans' => 'ALTER TABLE `table_with_one` ADD PRIMARY KEY (`id`) COMMENT \'主\'\'键\';',
        ],
        [
            'diff' => '+ INDEX `intfield` (`intfield`,`charfield`)',
            'trans' => 'ALTER TABLE `table_with_one` ADD INDEX `intfield` (`intfield`,`charfield`);',
        ],
        [
            'diff' => '+ UNIQUE `uniqueindex` (`intfield`)',
            'trans' => 'ALTER TABLE `table_with_one` ADD UNIQUE `uniqueindex` (`intfield`);',
        ],
        [
            'diff' => '+ INDEX `addfield` (`charfield`)',
            'trans' => 'ALTER TABLE `table_with_one` ADD INDEX `addfield` (`charfield`);',
        ],
        [
            'diff' => '-[0] `id` int(10) unsigned NOT NULL COMMENT \'ID\'' . "\n" . '+[0] `id` int(10) unsigned NOT NULL auto_increment COMMENT \'ID\'',
            'trans' => 'ALTER TABLE `table_with_one` CHANGE `id` `id` int(10) unsigned NOT NULL auto_increment COMMENT \'ID\' first;',
        ],
    ];

}
