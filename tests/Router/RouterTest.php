<?php

namespace Org\Snje\MinifwTest\Router;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class RouterTest extends Ts\TestCommon {

    /**
     * @covers Org\Snje\Minifw\Text::strip_html
     */
    public function test_multi_layer_info() {
        $hash = [
            '/' => ['', '', []],
            '/index' => ['', 'index', [],],
            '/www/index' => ['/www', 'index', [],],
            '/www/index-' => ['/www', 'index', [''],],
            '/www/qqq/index-12' => ['/www/qqq', 'index', ['12'],],
            '/www/qqq/index-1-2-3' => ['/www/qqq', 'index', ['1', '2', '3'],],
            '//www/qqq/index-1-2' => ['//www/qqq', 'index', ['1', '2'],],
        ];
        foreach ($hash as $k => $v) {
            $this->assertEquals($v, FW\Router::multi_layer_info($k));
        }
        $err = ['', 'www/3eee', '/www\\/qqq/index-1-2'];
        foreach ($err as $v) {
            try {
                FW\Router::multi_layer_info($v);
                $this->assertTrue(false);
            }
            catch (\Org\Snje\Minifw\Exception $ex) {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * @covers Org\Snje\Minifw\Text::strip_tags
     */
    public function test_single_layer_info() {
        $hash = [
            '/' => ['', '', ''],
            '/index' => ['', 'index', ''],
            '/www/index' => ['/www', 'index', ''],
            '/www/index-' => ['/www', 'index', '-'],
            '/www/qqq/index-12' => ['/www', 'qqq', '/index-12'],
            '//www/qqq/index-1-2' => ['/', 'www', '/qqq/index-1-2'],
            '/www\\/qqq/index-1-2' => ['', 'www', '\\/qqq/index-1-2'],
        ];
        foreach ($hash as $k => $v) {
            $this->assertEquals($v, FW\Router::single_layer_info($k));
        }
        $err = ['', 'www/3eee'];
        foreach ($err as $v) {
            try {
                FW\Router::single_layer_info($v);
                $this->assertTrue(false);
            }
            catch (\Org\Snje\Minifw\Exception $ex) {
                $this->assertTrue(true);
            }
        }
    }

}
