<?php

namespace Org\Snje\MinifwTest\JsonCall;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class TableTest extends Ts\TestCommon {

    public function test_sync_call() {
        $obj = Table::get();
        $db = $obj->get_db();
        $controler = new FW\Controler();
        $count = count(self::$input);
        for ($i = 0; $i < $count; $i++) {
            $ret = $controler->sync_call(
                    $db
                    , self::$input[$i]
                    , [$obj, 'func']
                    , FW\Controler::JSON_CALL_RETURN);
            $this->assertEquals(self::$expect[$i], $ret);
        }

        $ret = $controler->sync_call(
                $db
                , 'test msg'
                , [$obj, 'func_except']
                , FW\Controler::JSON_CALL_RETURN);
        $this->assertEquals([
            'error' => -1,
            'returl' => '',
            'msg' => '[' . __DIR__ . '/Table.php:15]test msg',
                ], $ret);

        $ret = $controler->sync_call(
                $db
                , 'test msg'
                , [$obj, 'func_noexist']
                , FW\Controler::JSON_CALL_RETURN);
        $this->assertEquals([
            'error' => -1,
            'returl' => '',
            'msg' => '操作失败',
                ], $ret);
    }

    public static $input = [
        false,
        true,
        [],
        [
            'returl' => 'testurl',
        ],
        [
            'msg' => 'testmsg',
        ],
        [
            'msg' => 'testmsg',
            'returl' => 'testurl',
        ],
        [
            'msg' => 'testmsg',
            'returl' => 'testurl',
            'msg1' => 'testmsg1',
        ],
        [
            'error' => 1,
            'msg' => 'testmsg',
            'returl' => 'testurl',
            'msg1' => 'testmsg1',
        ],
    ];
    public static $expect = [
        [
            'error' => -1,
            'msg' => '操作失败',
            'returl' => '',
        ],
        [
            'error' => 0,
            'returl' => '',
            'msg' => '',
        ],
        [
            'error' => 0,
            'returl' => '',
            'msg' => '',
        ],
        [
            'error' => 0,
            'returl' => 'testurl',
            'msg' => '',
        ],
        [
            'error' => 0,
            'returl' => '',
            'msg' => 'testmsg',
        ],
        [
            'error' => 0,
            'returl' => 'testurl',
            'msg' => 'testmsg',
        ],
        [
            'error' => 0,
            'returl' => 'testurl',
            'msg' => 'testmsg',
            'msg1' => 'testmsg1',
        ],
        [
            'error' => 1,
            'returl' => 'testurl',
            'msg' => 'testmsg',
            'msg1' => 'testmsg1',
        ],
    ];

}
