<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class Secoder {

    const EXPIRE = 600;

    //验证码字符集
    public static $code_set = '23456789ABCDEFGHJKLMNPRSTUVWXYZ';
    //验证码长度
    public static $code_len = 4;
    //字体大小
    public static $font_size = 13;
    //验证码宽度
    public static $width = 0;
    //验证码高度
    public static $height = 0;
    //验证码字符高度
    public static $pos_y = 0;
    //验证码起始位置
    public static $start = 0;
    //干扰线数量
    public static $noise = 0;

    public static function entry($key) {
        $bg = [243, 251, 254];
        if (self::$width == 0) {
            self::$width = self::$code_len * self::$font_size * 1.8 + self::$font_size * 2.0;
        }
        if (self::$height == 0) {
            self::$height = self::$font_size * 1.5;
        }
        $image = imagecreate(self::$width, self::$height);
        imagecolorallocate($image, $bg[0], $bg[1], $bg[2]);
        $_color = imagecolorallocate($image, mt_rand(1, 120), mt_rand(1, 120), mt_rand(1, 120));
        $ttfs = FW\Config::get()->get_config('fonts', 'secode');
        if (!is_array($ttfs) || count($ttfs) < 1) {
            throw new Exception('字体未指定');
        }
        $ttf_key = array_rand($ttfs);
        $ttf = WEB_ROOT . $ttfs[$ttf_key];

        $last_index = strlen(self::$code_set) - 1;
        for ($i = 0; $i < 10; $i++) {
            $noiseColor = imagecolorallocate($image, mt_rand(170, 225), mt_rand(170, 225), mt_rand(170, 225));
            for ($j = 0; $j < 5; $j++) {
                imagestring($image, 3, mt_rand(-10, self::$width), mt_rand(-10, self::$height), self::$code_set{mt_rand(0, $last_index)}, $noiseColor);
            }
        }

        $code = [];

        if (self::$start == 0) {
            $codeNX = self::$font_size * 0.5;
        }
        else {
            $codeNX = self::$start;
        }

        if (self::$pos_y == 0) {
            self::$pos_y = self::$font_size * 1.5;
        }

        for ($i = 0; $i < self::$code_len; $i++) {
            $code[$i] = self::$code_set[mt_rand(0, $last_index)];
            imagettftext($image, self::$font_size, mt_rand(-20, 40), $codeNX, self::$pos_y, $_color, $ttf, $code[$i]);
            $codeNX += mt_rand(self::$font_size * 1.2, self::$font_size * 1.5);
        }

        if (self::$noise > 0) {
            $xoffset = intval(self::$width / 10);
            $yoffset = intval(self::$height / 10);

            for ($i = 0; $i < self::$noise; $i++) {
                $begin_x = mt_rand($xoffset * -1, self::$width + $xoffset);
                $begin_y = mt_rand($yoffset * -1, self::$height + $yoffset);
                $end_x = mt_rand($xoffset * -1, self::$width + $xoffset);
                $end_y = mt_rand($yoffset * -1, self::$height + $yoffset);

                $noiseColor = imagecolorallocate($image, mt_rand(0, 120), mt_rand(0, 120), mt_rand(0, 120));
                imageline($image, $begin_x, $begin_y, $end_x, $end_y, $noiseColor);
            }
        }

        isset($_SESSION) || session_start();
        $_SESSION[$key]['code'] = join('', $code);
        $_SESSION[$key]['time'] = time();

        header('Pragma: no-cache');
        header("content-type: image/JPEG");

        imageJPEG($image);
        imagedestroy($image);
    }

    public static function test($str, $key) {
        $str = strtoupper($str);
        $time = time();
        if (!isset($_SESSION[$key])) {
            return false;
        }
        $code = $_SESSION[$key]['code'];
        $ctime = $_SESSION[$key]['time'];
        unset($_SESSION[$key]);

        if (($time - $ctime) > self::EXPIRE) {
            return false;
        }
        if ($str === $code) {
            return true;
        }
        return false;
    }

}
