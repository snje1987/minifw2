<?php

namespace Org\Snje\MinifwTest;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class TestCommon extends \PHPUnit_Framework_TestCase {

    /**
     * @coversNothing
     */
    public static function setUpBeforeClass() {
        FW\System::get(__DIR__ . '/config.php');
        //parent::setUpBeforeClass();
    }

}
