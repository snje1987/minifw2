<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class Tpl {

    public static $theme_path;
    public static $res_path;
    public static $compiled_path;
    protected static $_varis = [];
    public static $always_compile;
    protected static $tpl_dest = null;

    public static function assign($name, $value) {
        self::$_varis[$name] = $value;
    }

    public static function get($name) {
        if (isset(self::$_varis[$name])) {
            return self::$_varis[$name];
        }
        return null;
    }

    public static function append($name, $value) {
        if (isset(self::$_varis[$name])) {
            self::$_varis[$name] .= $value;
        }
        else {
            self::$_varis[$name] = $value;
        }
    }

    public static function prepend($name, $value) {
        if (isset(self::$_varis[$name])) {
            self::$_varis[$name] = $value . self::$_varis[$name];
        }
        else {
            self::$_varis[$name] = $value;
        }
    }

    public static function exist($tpl, $theme, $is_block = false) {
        if ($theme === null || $theme === '') {
            return false;
        }
        $path = '';
        if ($is_block) {
            $path = WEB_ROOT . self::$theme_path . '/' . $theme . '/block' . $tpl . '.html';
        }
        else {
            $path = WEB_ROOT . self::$theme_path . '/' . $theme . '/page' . $tpl . '.html';
        }
        return file_exists($path);
    }

    public static function display($tpl, $args, $theme, $return = false) {
        $tpl_src = WEB_ROOT . self::$theme_path . '/' . $theme . '/page' . $tpl . '.html';
        self::$tpl_dest = WEB_ROOT . self::$compiled_path . '/' . $theme . '/page' . $tpl . '.php';
        ob_start();
        try {
            self::_compile($tpl_src, self::$tpl_dest, $theme);
            extract(self::$_varis);
            include(self::$tpl_dest);
            if ($return) {
                return ob_get_clean();
            }
            else {
                ob_end_flush();
                return;
            }
        }
        catch (\Exception $ex) {
            ob_end_clean();
            throw $ex;
        }
    }

    protected static function _inc($tpl, $args, $theme) {
        $tpl_src = WEB_ROOT . self::$theme_path . '/' . $theme . '/block' . $tpl . '.html';
        self::$tpl_dest = WEB_ROOT . self::$compiled_path . '/' . $theme . '/block' . $tpl . '.php';
        self::_compile($tpl_src, self::$tpl_dest, $theme);
        extract(self::$_varis);
        include(self::$tpl_dest);
    }

    protected static function _compile($src, $dest, $theme) {
        if (!file_exists($src)) {
            if (DEBUG === 1) {
                throw new Exception('模板不存在：' . $src);
            }
            else {
                throw new Exception('模板不存在');
            }
        }

        $srctime = filemtime($src);
        $desttime = 0;
        if (file_exists($dest)) {
            $desttime = filemtime($dest);
        }
        if (self::$always_compile == 1 || $desttime == 0 || $desttime <= $srctime) {
            $str = file_get_contents($src);

            $str = self::_compile_string($str, $theme);

            FW\File::mkdir(dirname($dest));
            if (!file_put_contents($dest, $str)) {
                if (DEBUG === 1) {
                    throw new Exception('写入模板失败: ' . $dest);
                }
                else {
                    throw new Exception('写入模板失败');
                }
            }
        }
    }

    public static function trans_isset($matches) {
        $str = $matches[1];
        if ($str == '') {
            return '';
        }
        else {
            $array = explode('|', $str);
            $ret = '';
            $tail = '';
            $last = count($array) - 1;
            foreach ($array as $k => $item) {
                if ($k == 0 || $k < $last) {
                    $ret .= '(isset(' . $item . ')?(' . $item . '):';
                    $tail .= ')';
                }
                else {
                    $ret .= '(' . $item . ')';
                }
            }
            if ($last == 0) {
                $ret .= '\'\'';
            }
            return '<?=' . $ret . $tail . ';?>';
        }
    }

    protected static function _compile_string($input, $theme) {
        $input = preg_replace('/\<{inc \$(\S*?)\s*}\>/'
                , '<?php ' . __NAMESPACE__ . '\Tpl::_inc(\$$1,[],\'' . $theme . '\');?>', $input);

        $input = preg_replace('/\<{inc \$(\S*?) (\S*?)\s*}\>/'
                , '<?php ' . __NAMESPACE__ . '\Tpl::_inc(\$$1,$2,\'' . $theme . '\');?>', $input);

        $input = preg_replace('/\<{inc \$(\S*?) (\S*?) (\S*?)\s*}\>/'
                , '<?php ' . __NAMESPACE__ . '\Tpl::_inc(\$$1,$2,\'$3\');?>', $input);

        $input = preg_replace('/\<{inc \/?(\S*?)\s*}\>/'
                , '<?php ' . __NAMESPACE__ . '\Tpl::_inc(\'/$1\',[],\'' . $theme . '\');?>', $input);

        $input = preg_replace('/\<{inc \/?(\S*?) (\S*?)\s*}\>/'
                , '<?php ' . __NAMESPACE__ . '\Tpl::_inc(\'/$1\',$2,\'' . $theme . '\');?>', $input);

        $input = preg_replace('/\<{inc \/?(\S*?) (\S*?) (\S*?)\s*}\>/'
                , '<?php ' . __NAMESPACE__ . '\Tpl::_inc(\'/$1\',$2,\'$3\');?>', $input);

        $input = preg_replace_callback('/\<{\|(.*?)}\>/', __NAMESPACE__ . '\Tpl::trans_isset', $input);
        $input = preg_replace('/\<{=(.*?)}\>/', '<?=($1);?>', $input);
        $input = preg_replace('/\<{if (.*?)}\>/', '<?php if($1){?>', $input);
        $input = preg_replace('/\<{elseif (.*?)}\>/', '<?php }elseif($1){?>', $input);
        $input = preg_replace('/\<{else}\>/', '<?php }else{?>', $input);
        $input = preg_replace('/\<{\/if}\>/', '<?php }?>', $input);

        $input = preg_replace('/\<{for (\S*?) (\S*?) (\S*?)\s*?}\>/', '<?php for($1=$2;$1<=$3;$1++){?>', $input);

        $input = preg_replace('/\<{\/for}\>/', '<?php }?>', $input);

        $input = preg_replace('/\<{foreach (\S*?) (\S*?)}\>/', '<?php foreach($1 as $2){?>', $input);

        $input = preg_replace('/\<{foreach (\S*?) (\S*?) (\S*?)\s*?}\>/', '<?php foreach($1 as $2=>$3){?>', $input);

        $input = preg_replace('/\<{\/foreach}\>/', '<?php }?>', $input);
        $input = preg_replace('/\<{\*((.|\r|\n)*?)\*}\>/', '', $input);
        $input = preg_replace('/\<{(\S.*?)}\>/', '<?php $1;?>', $input);

        //path relate to theme："/xxxx/yyyy"
        $input = preg_replace('/\<link (.*?)href="\/([^"]*)"(.*?) \/\>/i', '<link $1href="' . self::$res_path . self::$theme_path . '/' . $theme . '/$2"$3 />', $input);
        $input = preg_replace('/\<script (.*?)src="\/([^"]*)"(.*?)\>/i', '<script $1src="' . self::$res_path . self::$theme_path . '/' . $theme . '/$2"$3>', $input);
        $input = preg_replace('/\<img (.*?)src="\/([^"]*)"(.*?) \/\>/i', '<img $1src="' . self::$res_path . self::$theme_path . '/' . $theme . '/$2"$3 />', $input);
        $input = preg_replace('/url\(\/([^)]*)\)/i', 'url(\'' . self::$res_path . self::$theme_path . '/' . $theme . '/$1\')', $input);

        //path relate to resource root："|xxx/yyy"
        $input = preg_replace('/\<link (.*?)href="\|([^"]*)"(.*?) \/\>/i', '<link $1href="' . self::$res_path . '/$2"$3 />', $input);
        $input = preg_replace('/\<script (.*?)src="\|([^"]*)"(.*?)\>/i', '<script $1src="' . self::$res_path . '/$2"$3>', $input);
        $input = preg_replace('/\<img (.*?)src="\|([^"]*)"(.*?) \/\>/i', '<img $1src="' . self::$res_path . '/$2"$3 />', $input);
        $input = preg_replace('/url\(\|([^)]*)\)/i', 'url(\'' . self::$res_path . '/$1\')', $input);

        //path keep original："\xxx/yyy"
        $input = preg_replace('/\<link (.*?)href="\\\([^"]*)"(.*?) \/\>/i', '<link $1href="/$2"$3 />', $input);
        $input = preg_replace('/\<script (.*?)src="\\\([^"]*)"(.*?)\>/i', '<script $1src="/$2"$3>', $input);
        $input = preg_replace('/\<img (.*?)src="\\\([^"]*)"(.*?) \/\>/i', '<img $1src="/$2"$3 />', $input);
        $input = preg_replace('/url\(\\\([^)]*)\)/i', 'url(\'/$1\')', $input);

        //remove empty character
        //$input = preg_replace('/^\s*(.*?)\s*$/im', '$1', $input);
        //$input = preg_replace('/\r|\n/', '', $input);
        //$input = preg_replace('/\>\s*\</', '>$1<', $input);
        $input = preg_replace('/\s*\?\>\s*\<\?php\s*/', '', $input);
        //$input = preg_replace('/\>\s*(.*?)\s*\</', '>$1<', $input);
        //$input = preg_replace('/\s{2,}/i', ' ', $input);
        $input = preg_replace('/\?\>$/i', "\n", $input);
        return $input;
    }

}

$config = Config::get();
Tpl::$always_compile = $config->get_config('debug', 'tpl_always_compile', 0);
Tpl::$theme_path = $config->get_config('path', 'theme');
Tpl::$res_path = $config->get_config('path', 'res');
Tpl::$compiled_path = $config->get_config('path', 'compiled');
