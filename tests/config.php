<?php

include_once dirname(__DIR__) . '/config.php';

$cfg['debug']['debug'] = 1;
$cfg['main']['dbprefix'] = '';
$cfg['main']['theme'] = 'def';
$cfg['main']['db'] = 'Mysqli';

$cfg['path'] = [
    'theme' => '/theme',
    'res' => '/www',
    'compiled' => '/tmp/compiled',
    'caroot' => '/tmp/caroot.pem',
    'web_root' => dirname(__DIR__),
];

