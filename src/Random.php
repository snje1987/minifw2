<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class Random {

    public static $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
    public static $alphas = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    public static $digits = '0123456789';
    protected static $char_len;
    protected static $alpha_len;
    protected static $digit_len;

    public static function gen_int($min, $max) {
        if (function_exists('random_int')) {
            return random_int($min, $max);
        }
        return mt_rand($min, $max);
    }

    public static function gen_byte($len, $bin = false) {
        $byte = null;
        if (function_exists('random_bytes')) {
            $byte = random_bytes($len);
        }
        if (function_exists('mcrypt_create_iv')) {
            $byte = mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            $byte = openssl_random_pseudo_bytes($len);
        }
        if ($bin) {
            return $byte;
        } else {
            return bin2hex($byte);
        }
    }

    public static function gen_key($len) {
        $key = '';
        for ($i = 0; $i < $len; $i++) {
            $key .= self::$chars[self::gen_int(0, self::$char_len - 1)];
        }
        return $key;
    }

    public static function gen_str($len) {
        $key = '';
        for ($i = 0; $i < $len; $i++) {
            $key .= self::$alphas[self::gen_int(0, self::$alpha_len - 1)];
        }
        return $key;
    }

    public static function gen_num($len) {
        $key = '';
        for ($i = 0; $i < $len; $i++) {
            $key .= self::$digits[self::gen_int(0, self::$digit_len - 1)];
        }
        return $key;
    }

    public static function init() {
        self::$char_len = strlen(self::$chars);
        self::$alpha_len = strlen(self::$alphas);
        self::$digit_len = strlen(self::$digits);
    }

}

Random::init();
