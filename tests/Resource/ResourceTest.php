<?php

namespace Org\Snje\MinifwTest\Resource;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class ResourceTest extends Ts\TestCommon {

    public function test_compile_all() {
        FW\File::clear_dir('/tests/Resource/to');
        $resource_obj = new FW\Resource(__DIR__ . '/resource_map.php');
        $resource_obj->compile_all();
        $file_list = [
            'copy/file1.css',
            'cssmin/file1.css',
            'cssmin/dir1/file2.css',
            'cssmin/dir1/file3.css',
            'uglify/file1.js',
            'uglify/dir1/file2.js',
            'uglify/dir1/file3.js',
            'copy/dir1/file2.js',
            'copy/dir1/file3.js',
            'copy/dir2/file4.js',
            'copy/dir2/file4.css',
            'copy/mdir/dir1/file2.js',
            'copy/mdir/dir1/file3.js',
            'copy/mdir/dir2/file4.js',
        ];
        $expected = __DIR__ . '/expected/';
        $to = __DIR__ . '/to/';
        foreach ($file_list as $file) {
            $this->assertFileEquals($expected . $file, $to . $file);
        }
        FW\File::clear_dir('/tests/Resource/to');
        FW\File::delete('/tests/Resource/to');
    }

}
