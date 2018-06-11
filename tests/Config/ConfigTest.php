<?php

namespace Org\Snje\MinifwTest\File;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class ConfigTest extends \PHPUnit_Framework_TestCase {

    /**
     * @coversNothing
     */
    public static function setUpBeforeClass() {

    }

    public function test_config() {
        $cfg = [];
        require __DIR__ . '/../../src/defaults.php';
        require __DIR__ . '/config.php';
        $config_obj = FW\Config::get_new(__DIR__ . '/config.php');
        foreach ($cfg as $k => $v) {
            $this->assertEquals($v, $config_obj->get_config($k));
        }
        $this->assertEquals('Mysqli', $config_obj->get_config('main', 'db'));
        $this->assertNull($config_obj->get_config('sqlite12'));
        $this->assertNull($config_obj->get_config('sqlite', 'name'));
    }

}
