<?php

namespace Org\Snje\MinifwTest\Table;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class CreateTest extends Ts\TestCommon {

    public function test_create() {
        $table_create = TableWithAll::get();
        $table_create->drop();
        $table_create->create();

        $db = \Org\Snje\Minifw\DB::get_default();

        $sql = 'show create table `' . $table_create::$tbname . '`';
        $ret = $db->get_query($sql);
        $this->assertArrayHasKey(0, $ret);
        $ret = $ret[0];
        $leftsql = $ret['Create Table'];

        $rightsql = 'CREATE TABLE `table_with_all` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT \'ID\',
  `intfield` int(11) NOT NULL COMMENT \'A int field\',
  `charfield` varchar(200) NOT NULL COMMENT \'A varchar field\',
  `textfield` text NOT NULL COMMENT \'A text field\',
  `intfield_def` int(11) NOT NULL DEFAULT \'0\' COMMENT \'A int field\',
  `charfield_def` varchar(200) NOT NULL DEFAULT \'\' COMMENT \'A varchar field\',
  PRIMARY KEY (`id`) COMMENT \'主键\',
  UNIQUE KEY `uniqueindex` (`intfield`),
  KEY `charfield` (`charfield`),
  KEY `intfield` (`intfield`,`charfield`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'Table To Create\'';

        $this->assertEquals($rightsql, $leftsql);

        $table_create = TableWithOne::get();
        $table_create->drop();
        $table_create->create();

        $sql = 'show create table `' . $table_create::$tbname . '`';
        $ret = $db->get_query($sql);
        $this->assertArrayHasKey(0, $ret);
        $ret = $ret[0];
        $leftsql = $ret['Create Table'];

        $rightsql = 'CREATE TABLE `table_with_one` (
  `intfield` int(11) NOT NULL COMMENT \'A int field\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'Table To Create\'';

        $this->assertEquals($rightsql, $leftsql);
    }

}
