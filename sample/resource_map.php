<?php

$value = [];
$value[] = [
    'method' => 'js',
    'type' => 'dir',
    'tail' => '.js',
    'map' => [
        '/www/theme/default/script/' => '/theme/default/script/',
    ],
];
$value[] = [
    'method' => 'css',
    'type' => 'file',
    'map' => [
        '/www/theme/default/style/common.css' => '/theme/default/style/common.css',
    ],
];
return $value;
