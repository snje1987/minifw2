<?php

namespace Org\Snje\MinifwTest\Table;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class GetTest extends Ts\TestCommon {

    public function test_get() {
        $table1 = TableWithAll::get();

        $this->assertEquals('Org\Snje\MinifwTest\Table\TableWithAll', get_class($table1));

        $table2 = TableWithOne::get();

        $this->assertEquals('Org\Snje\MinifwTest\Table\TableWithOne', get_class($table2));

        $this->assertNotEquals($table2, $table1);

        $table3 = TableWithAll::get();

        $this->assertEquals($table1, $table3);

        $table4 = TableWithAll::get([], 'def');

        $this->assertEquals($table1, $table4);
    }

}
