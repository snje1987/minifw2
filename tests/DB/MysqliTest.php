<?php

namespace Org\Snje\MinifwTest\DB;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class MysqliTest extends Ts\TestCommon {

    public function test_compile_sql() {
        $cases = [
            [
                'sql' => 'select `name` from `table` where `name` = {value}',
                'var' => [
                    'value' => 'real_value<>_%'
                ],
                'out' => 'select `name` from `table` where `name` = \'real_value&lt;&gt;_%\'',
            ],
            [
                'sql' => 'select `name` from `table` where `name` = {value}',
                'var' => [
                    'value' => ['rich', 'real_value<>_%'],
                ],
                'out' => 'select `name` from `table` where `name` = \'real_value<>_%\'',
            ],
            [
                'sql' => 'select `name` from `table` where `name` = {value}',
                'var' => [
                    'value' => ['expr', '\'real_value<>_%\''],
                ],
                'out' => 'select `name` from `table` where `name` = \'real_value<>_%\'',
            ],
            [
                'sql' => 'select `name` from `table` where `name` like \'%{value}_\'',
                'var' => [
                    'value' => ['like', 'real_value<>_%'],
                ],
                'out' => 'select `name` from `table` where `name` like \'%real\_value<>\_\%_\'',
            ],
        ];
        $db = FW\DB\Mysqli::get();
        foreach ($cases as $v) {
            $this->assertEquals($v['out'], $db->compile_sql($v['sql'], $v['var']));
        }
    }

}
