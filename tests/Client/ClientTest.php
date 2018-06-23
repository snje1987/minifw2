<?php

namespace Org\Snje\MinifwTest\File;

use Org\Snje\Minifw as FW;
use Org\Snje\MinifwTest as Ts;

class ClientTest extends Ts\TestCommon {

    /**
     * @coversNothing
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $base = str_replace('\\', '/', dirname(__FILE__));
        $path = $base . '/tmp';
        FW\File::clear_dir($path, true);
        FW\File::delete($path, true);
    }

    public function test_update_caroot() {
        $client = new FW\Client();
        $client->update_caroot();
    }

    public function test_set_uption() {
        $normal = [
            ['timeout', 0],
            ['timeout', 30],
            ['user_agent', 'test'],
            ['cookie', 'test'],
            ['referer', 'test'],
            ['header', '0'],
            ['handle_cookie', false],
            ['handle_referer', false],
            ['handle_cookie', 1],
            ['handle_referer', 1],
        ];
        $except = [
            ['timeout', -5],
            ['user_agent', ''],
            ['cookie', ''],
            ['referer', ''],
            ['header', ''],
        ];

        $client = new FW\Client();

        foreach ($normal as $v) {
            try {
                $client->set_option($v[0], $v[1]);
            }
            catch (\Exception $ex) {
                $this->fail($ex->getMessage());
            }
        }

        foreach ($except as $v) {
            try {
                $client->set_option($v[0], $v[1]);
                $this->fail($ex->getMessage());
            }
            catch (\Exception $ex) {

            }
        }
    }

    public function test_get() {
        $tests = [
            [
                'url' => 'http://www.baidu.com',
                'result' => [
                    'error' => 0,
                    'http_code' => 302,
                    'redirect_url' => 'https://www.baidu.com/',
                    'content_type' => 'text/html',
                ],
            ],
            [
                'url' => 'https://www.baidu.com',
                'result' => [
                    'error' => 0,
                    'http_code' => 200,
                    'redirect_url' => '',
                    'content_type' => 'text/html',
                ],
            ],
        ];

        $client = new FW\Client();
        foreach ($tests as $test) {
            $result = $client->get($test['url']);
            foreach ($test['result'] as $k => $v) {
                $this->assertEquals($v, $result[$k]);
            }
        }
    }

}
