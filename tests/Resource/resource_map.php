<?php

$value = [];
$value[] = [
    'method' => 'js',
    'type' => 'file',
    'map' => [
        '/tests/Resource/to/uglify/file1.js' => '/tests/Resource/from/file1.js',
    ],
];
$value[] = [
    'method' => 'css',
    'type' => 'file',
    'map' => [
        '/tests/Resource/to/cssmin/file1.css' => '/tests/Resource/from/file1.css',
    ],
];
$value[] = [
    'method' => 'copy',
    'type' => 'file',
    'map' => [
        '/tests/Resource/to/copy/file1.css' => '/tests/Resource/from/file1.css',
    ],
];
$value[] = [
    'method' => 'copy',
    'type' => 'file',
    'map' => [
        '/tests/Resource/to/copy/dir2/file4.js' => '/tests/Resource/from/dir2/file4.js',
        '/tests/Resource/to/copy/dir2/file4.css' => '/tests/Resource/from/dir2/file4.css',
    ],
];

$value[] = [
    'method' => 'js',
    'type' => 'dir',
    'tail' => '.js',
    'map' => [
        '/tests/Resource/to/uglify/dir1' => '/tests/Resource/from/dir1',
    ],
];
$value[] = [
    'method' => 'css',
    'type' => 'dir',
    'tail' => '.css',
    'map' => [
        '/tests/Resource/to/cssmin/dir1' => '/tests/Resource/from/dir1',
    ],
];
$value[] = [
    'method' => 'copy',
    'type' => 'dir',
    'tail' => '.js',
    'map' => [
        '/tests/Resource/to/copy/dir1' => '/tests/Resource/from/dir1',
    ],
];
$value[] = [
    'method' => 'copy',
    'type' => 'dir',
    'tail' => '.js',
    'map' => [
        '/tests/Resource/to/copy/mdir/dir2' => '/tests/Resource/from/dir2',
        '/tests/Resource/to/copy/mdir/dir1' => '/tests/Resource/from/dir1',
    ],
];
return $value;
