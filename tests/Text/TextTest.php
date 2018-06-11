<?php

namespace Org\Snje\MinifwTest\Text;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class TextTest extends Ts\TestCommon {

    public function test_strip_html() {
        $hash = [
            " 123456   \n  2345 \n" => "123456 2345",
            " 123456\n2345 \n" => "123456 2345",
            " 123456\n   //not show  \n2345 \n" => "123456 2345",
            "> 12345 <" => ">12345<",
            ">\n12345 <" => ">12345<",
        ];
        foreach ($hash as $k => $v) {
            $this->assertEquals($v, FW\Text::strip_html($k));
        }
    }

    public function test_strip_tag() {
        $hash = [
            "<p>123</p>" => "123",
            "<br />123<br/>" => "123",
            "<p style=\"font-size:12px; color:red\">123</p>" => "123",
            "<p style=\"font-size:12px; color:red\" >123</p>" => "123",
            "<?ss style=\"font-size:12px; color:red\" >123</p>" => "<?ss style=\"font-size:12px; color:red\" >123",
        ];
        foreach ($hash as $k => $v) {
            $this->assertEquals($v, FW\Text::strip_tags($k));
        }
    }

}
