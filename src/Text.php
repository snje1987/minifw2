<?php

namespace Org\Snje\Minifw;

class Text {

    /**
     * 压缩html数据
     *
     * @param string $str 要压缩的数据
     * @return string 压缩后的数据
     */
    public static function strip_html($str) {
        $str = preg_replace('/^\s*(.*?)\s*$/im', '$1', $str);
        $str = preg_replace('/^\/\/(.*?)$/im', '', $str);
        $str = preg_replace('/\r|\n/i', ' ', $str);
        $str = preg_replace('/\>\s*(.*?)\s*\</im', '>$1<', $str);
        $str = preg_replace('/\s{2,}/i', ' ', $str);
        return $str;
    }

    /**
     * 清除所有的html标记
     *
     * @param string $str 要处理的数据
     * @return string 处理后的数据
     */
    public static function strip_tags($str) {
        return preg_replace('/\<(\/?[a-zA-Z0-9]+)(\s+[^>]*)?\/?\>/i', '', $str);
    }

    /**
     * 判断字符串中是否具有html标记
     *
     * @param string $str 要判断的字符串
     * @return bool 具有标记返回true，否则返回fasle
     */
    public static function is_rich($str) {
        return preg_match('/\<(\/?[a-zA-Z0-9]+)(\s+[^>]*)?\/?\>/i', $str);
    }

    /**
     * 清除标记后截取指定长度的字符串
     *
     * @param string $str 要截取的字符串
     * @param int $len 要截取的长度
     * @return string 截取的结果
     */
    public static function sub_text($str, $len) {
        $encoding = Config::get()->get_config('main', 'encoding');
        $str = self::strip_tags($str);
        $str = preg_replace('/(\s|&nbsp;)+/i', ' ', $str);
        return mb_substr($str, 0, $len, $encoding);
    }

    /**
     * 截取指定长度的具有基本格式的字符串
     *
     * @param string $str 要截取的字符串
     * @param int $len 要截取的长度
     * @return string 截取的结果
     */
    public static function sub_rich($str, $len) {
        $encoding = Config::get()->get_config('main', 'encoding');
        if (self::is_rich($str)) {
            $str = self::strip_html($str);
            $str = preg_replace('/\r/i', '', preg_replace('/\n/i', '', $str));
            $str = preg_replace('/\<br[^>]*\>/i', "\n", preg_replace('/\<p[^>]*\>/i', "\n", $str));
            $str = self::strip_tags($str);
        }
        $str = preg_replace('/^\s*\n/im', '', preg_replace('/(\t| |　|&nbsp;)+/i', ' ', $str));
        $str = mb_substr($str, 0, $len, $encoding);
        $str = preg_replace('/^([^\r\n]*)\r?\n?$/im', "<p>$1</p>", $str);
        return $str;
    }

    /**
     * 计算字符串长度
     *
     * @param string $str 字符串
     * @return int 长度
     */
    public static function str_len($str) {
        $encoding = Config::get()->get_config('main', 'encoding');
        return mb_strlen($str, $encoding);
    }

    public static function is_email($str) {
        if (!filter_var($str, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    public static function is_phone($str) {
        if (!preg_match("/^1\d{10}$/", $str)) {
            return false;
        }
        return true;
    }

    public static function is_tel($str) {
        if (!preg_match("/^\d{3,4}-\d{7,8}(-\d{1,6})?$/", $str)) {
            return false;
        }
        return true;
    }

    public static function is_num($str) {
        if (!preg_match("/^-?\d+(\.\d+)?$/", $str)) {
            return false;
        }
        return true;
    }

    public static function is_expr($str) {
        if (!preg_match("/^[-(]*\d+(\.\d+)?[)]*$/", $str)) {
            return false;
        }
        return true;
    }

    public static function is_positive($str) {
        if (!preg_match("/^\d+(\.\d+)?$/", $str)) {
            return false;
        }
        return true;
    }

}
