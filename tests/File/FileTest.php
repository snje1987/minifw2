<?php

namespace Org\Snje\MinifwTest\File;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class FileTest extends Ts\TestCommon {

    /**
     * @coversNothing
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $base = str_replace('\\', '/', dirname(__FILE__));
        $path = $base . '/to';
        FW\File::clear_dir($path, true);
        FW\File::delete($path, true);
    }

    /**
     * @covers Org\Snje\Minifw\Text::strip_html
     */
    public function test_copy() {
        $base = str_replace('\\', '/', dirname(__FILE__));
        $from = $base . '/from/file1';
        $to = $base . '/to/file1';
        FW\File::copy($from, $to);
        $this->assertFileEquals($from, $to);
    }

    public function test_rename_file() {
        $base = str_replace('\\', '/', dirname(__FILE__));
        $from = $base . '/to/file1';
        $to = $base . '/to/newfile1';
        FW\File::rename($from, $to);
        $this->assertFileEquals($base . '/from/file1', $to);
    }

    /**
     * @covers Org\Snje\Minifw\Text::strip_tags
     */
    public function test_delete() {
        $base = str_replace('\\', '/', dirname(__FILE__));
        $path = $base . '/to/newfile1';
        FW\File::delete($path, true);
        $this->assertFileNotExists($path);
    }

    public function test_copy_dir() {
        $base = str_replace('\\', '/', dirname(__FILE__));
        $from = $base . '/from/dir';
        $to = $base . '/to/dir';
        FW\File::copy_dir($from, $to);
        $this->assertFileEquals($from . '/dirfile1', $to . '/dirfile1');
        $this->assertFileEquals($from . '/sub/dirfile2', $to . '/sub/dirfile2');
    }

    public function test_rename_dir() {
        $base = str_replace('\\', '/', dirname(__FILE__));
        $origin = $base . '/from/dir';
        $from = $base . '/to/dir';
        $to = $base . '/to/newdir';
        FW\File::rename($from, $to);
        $this->assertFileEquals($origin . '/dirfile1', $to . '/dirfile1');
        $this->assertFileEquals($origin . '/sub/dirfile2', $to . '/sub/dirfile2');
    }

    public function test_clear_dir() {
        $base = str_replace('\\', '/', dirname(__FILE__));
        $path = $base . '/to/newdir';
        FW\File::clear_dir($path, true);
        $this->assertFileNotExists($path . '/sub');
        $this->assertFileNotExists($path . '/dirfile1');
        $this->assertFileExists($path);
        FW\File::delete($path, true);
        $this->assertFileNotExists($path);
        $this->assertFileNotExists(dirname($path));
    }

    public function test_format_path() {
        $cases = [
            [
                'in' => ['/'],
                'out' => '/',
            ],
            [
                'in' => ['/', '/'],
                'out' => '/',
            ],
            [
                'in' => ['/12321/ddd/', '/', 'config.php'],
                'out' => '/config.php',
            ],
            [
                'in' => ['/', 'aaa', 'config.php'],
                'out' => '/aaa/config.php',
            ],
            [
                'in' => ['aaa', 'bbb', 'config.php'],
                'out' => 'aaa/bbb/config.php',
            ],
            [
                'in' => ['aaa/', './bbb/', 'config.php'],
                'out' => 'aaa/bbb/config.php',
            ],
            [
                'in' => ['aaa/', '', '../bbb/', 'config.php'],
                'out' => 'bbb/config.php',
            ],
            [
                'in' => ['a/b/c/', '../../v', 'config.php'],
                'out' => 'a/v/config.php',
            ],
            [
                'in' => ['a/b/c/', '../../../../v', 'config.php'],
                'out' => '',
            ],
        ];
        foreach ($cases as $case) {
            $out = call_user_func_array('Org\Snje\Minifw\File::format_path', $case['in']);
            $this->assertEquals($case['out'], $out);
        }
    }

}
