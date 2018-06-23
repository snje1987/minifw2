<?php

namespace Org\Snje\MinifwTest\File;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class ImageTest extends Ts\TestCommon {

    protected static $img_list = [
        '001.jpg',
        '002.png',
        '003.gif',
    ];

    /**
     * @coversNothing
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $path = WEB_ROOT . '/tmp/image';
        FW\File::clear_dir($path, true);
        FW\File::copy_dir(__DIR__ . '/image', $path);
    }

    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();
        //$path = WEB_ROOT . '/tmp/image';
        //FW\File::clear_dir($path, true);
        //FW\File::delete($path);
    }

    public function test_image_scale() {
        $path = '/tmp/image';

        foreach (self::$img_list as $img) {
            $old_info = getimagesize(WEB_ROOT . $path . '/' . $img);

            FW\Image::image_scale($path . '/' . $img, '_0_0', 0, 0);

            $new_path = FW\File::appent_tail($path . '/' . $img, '_0_0');
            $new_info = getimagesize(WEB_ROOT . $new_path);
            $this->assertEquals($old_info, $new_info);

            FW\Image::image_scale($path . '/' . $img, '_100_0', 100, 0);

            $new_path = FW\File::appent_tail($path . '/' . $img, '_100_0');
            $new_info = getimagesize(WEB_ROOT . $new_path);
            $this->assertLessThanOrEqual(100, $new_info[0]);

            FW\Image::image_scale($path . '/' . $img, '_0_100', 0, 100);

            $new_path = FW\File::appent_tail($path . '/' . $img, '_0_100');
            $new_info = getimagesize(WEB_ROOT . $new_path);
            $this->assertLessThanOrEqual(100, $new_info[1]);

            FW\Image::image_scale($path . '/' . $img, '_100_100', 100, 100);

            $new_path = FW\File::appent_tail($path . '/' . $img, '_100_100');
            $new_info = getimagesize(WEB_ROOT . $new_path);
            $this->assertLessThanOrEqual(100, $new_info[0]);
            $this->assertLessThanOrEqual(100, $new_info[1]);
        }
    }

    public function test_image_scale_padding() {
        $path = '/tmp/image';

        foreach (self::$img_list as $img) {
            FW\Image::image_scale_padding($path . '/' . $img, '_100_100_padding', 100, 100);

            $new_path = FW\File::appent_tail($path . '/' . $img, '_100_100_padding');
            $new_info = getimagesize(WEB_ROOT . $new_path);
            $this->assertEquals(100, $new_info[0]);
            $this->assertEquals(100, $new_info[1]);
        }
    }

    public function test_image_scale_cut() {
        $path = '/tmp/image';

        foreach (self::$img_list as $img) {
            for ($i = 1; $i <= 5; $i++) {
                FW\Image::image_scale_cut($path . '/' . $img, '_100_100_cut_' . $i, 100, 100, $i);

                $new_path = FW\File::appent_tail($path . '/' . $img, '_100_100_cut_' . $i);
                $new_info = getimagesize(WEB_ROOT . $new_path);
                $this->assertEquals(100, $new_info[0]);
                $this->assertEquals(100, $new_info[1]);
            }
        }
    }

}
