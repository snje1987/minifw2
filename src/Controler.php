<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class Controler {

    const DEFAULT_FUNCTION = '';
    const JSON_CALL_DIE = 0;
    const JSON_CALL_RETURN = 1;
    const JSON_CALL_REDIRECT = 2;
    const JSON_ERROR_OK = 0;
    const JSON_ERROR_UNKNOWN = -1;

    public static $cache_time;

    /**
     *
     * @var FW\Config
     */
    protected $config;
    protected $theme;

    public static function send_download_header($filename) {
        header("Accept-Ranges: bytes");
        $ua = isset($_SERVER["HTTP_USER_AGENT"]) ? strval($_SERVER["HTTP_USER_AGENT"]) : '';
        if (strpos($ua, "Edge") || strpos($ua, "MSIE") || (strpos($ua, 'rv:11.0') && strpos($ua, 'Trident'))) {
            $encoded_filename = urlencode($filename);
            $encoded_filename = str_replace("+", "%20", $encoded_filename);
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        }
        elseif (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        }
        else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
    }

    public static function redirect($url) {
        if (!headers_sent()) {
            header('Location:' . $url);
        }
        else {
            echo '<script type="text/javascript">window.location="' . $url . '";</script>';
        }
    }

    public static function show_301($url) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $url);
    }

    public static function readfile_with_304($file, $fsencoding = '') {
        $full = File::conv_to($file, $fsencoding);
        $mtime = \filemtime($full);
        $expire = gmdate('D, d M Y H:i:s', time() + self::$cache_time) . ' GMT';
        header('Expires: ' . $expire);
        header('Pragma: cache');
        header('Cache-Control: max-age=' . self::$cache_time);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('Etag: ' . $mtime);
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $mtime) {
            header('HTTP/1.1 304 Not Modified');
        }
        else {
            File::readfile($full);
        }
    }

    public static function host() {
        $url = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $url .= 's';
        }
        $url .= '://' . $_SERVER['HTTP_HOST'];
        return $url;
    }

    public static function url() {
        return self::host() . $_SERVER['REQUEST_URI'];
    }

    public static function referer($default = null) {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $url = strval($_SERVER['HTTP_REFERER']);
        }
        else {
            $url = $default;
        }
        return $url;
    }

    public static function json_call($post, $call, $mode = self::JSON_CALL_DIE) {
        $ret = [
            'error' => self::JSON_ERROR_UNKNOWN,
            'returl' => '',
            'msg' => '',
        ];
        try {
            $value = false;
            if (is_callable($call)) {
                $value = call_user_func($call, $post);
            }
            if (is_array($value)) {
                $ret = $value;
                if (!isset($ret['error'])) {
                    $ret['error'] = self::JSON_ERROR_OK;
                }
                if (!isset($ret['returl'])) {
                    if (is_array($post) && isset($post['returl'])) {
                        $ret['returl'] = urldecode(strval($post['returl']));
                    }
                    else {
                        $ret['returl'] = '';
                    }
                }
                if (!isset($ret['msg'])) {
                    $ret['msg'] = '';
                }
            }
            elseif ($value === true) {
                $ret['error'] = self::JSON_ERROR_OK;
                if (is_array($post) && isset($post['returl'])) {
                    $ret['returl'] = urldecode(strval($post['returl']));
                }
            }
            else {
                $ret['msg'] = '操作失败';
            }
        }
        catch (Exception $e) {
            $ret['error'] = $e->getCode();
            if (DEBUG === 1) {
                $ret['msg'] = '[' . $e->getFile() . ':' . $e->getLine() . ']' . $e->getMessage();
            }
            else {
                $ret['msg'] = $e->getMessage();
            }
        }
        catch (\Exception $e) {
            $ret['error'] = self::JSON_ERROR_UNKNOWN;
            if (DEBUG === 1) {
                $ret['msg'] = '[' . $e->getFile() . ':' . $e->getLine() . ']' . $e->getMessage();
            }
            else {
                $ret['msg'] = '操作失败';
            }
        }
        if ($mode == self::JSON_CALL_REDIRECT) {
            // @codeCoverageIgnoreStart
            if ($ret['returl'] != '') {
                self::redirect($ret['returl']);
            }
            else {
                self::redirect(self::referer('/'));
            }
            die(0);
            // @codeCoverageIgnoreEnd
        }
        elseif ($mode == self::JSON_CALL_DIE) {
            // @codeCoverageIgnoreStart
            die(\json_encode($ret, JSON_UNESCAPED_UNICODE));
            // @codeCoverageIgnoreEnd
        }
        else {
            return $ret;
        }
    }

    /**
     *
     * @param FW\DB $db
     * @param array $post
     * @param callback $call
     * @param int $mode
     */
    public static function sync_call($db, $post, $call, $mode = self::JSON_CALL_DIE) {
        $db->begin();
        $ret = self::json_call($post, $call, self::JSON_CALL_RETURN);
        if ($ret['error'] === self::JSON_ERROR_OK) {
            $db->commit();
        }
        else {
            $db->rollback();
        }
        if ($mode == self::JSON_CALL_REDIRECT) {
            // @codeCoverageIgnoreStart
            if ($ret['returl'] != '') {
                self::redirect($ret['returl']);
            }
            else {
                self::redirect(self::referer('/'));
            }
            die(0);
            // @codeCoverageIgnoreEnd
        }
        elseif ($mode == self::JSON_CALL_DIE) {
            // @codeCoverageIgnoreStart
            die(\json_encode($ret, JSON_UNESCAPED_UNICODE));
            // @codeCoverageIgnoreEnd
        }
        else {
            return $ret;
        }
    }

    public function __construct() {
        $this->config = Config::get();
        $this->theme = $this->config->get_config('main', 'theme', null);
    }

    /**
     * Call controler function according to the given name.
     * @param type $func function name
     * @param type $args args.
     */
    public function dispatch($function, $args) {
        $class_name = get_class($this);
        $class = new \ReflectionClass($class_name);
        if ($function == '') {
            $function = $class->getConstant('DEFAULT_FUNCTION');
        }
        $function = str_replace('.', '', $function);
        if ($function == '') {
            return $this->show_404();
        }
        $function = 'c_' . $function;
        if (!$class->hasMethod($function)) {
            return $this->show_404();
        }

        $func = $class->getMethod($function);
        $func->setAccessible(true);
        try {
            $func->invoke($this, $args);
        }
        catch (\Exception $ex) {
            if (DEBUG === 1) {
                return $this->show_msg($ex->getMessage(), 'Error');
            }
            else {
                return $this->show_404();
            }
        }
    }

    public function show_msg($content, $title = '', $link = '') {
        if (Tpl::exist('/msg', $this->theme)) {
            Tpl::assign('content', $content);
            Tpl::assign('title', $title);
            Tpl::assign('link', $link);
            Tpl::display('/msg', $this, $this->theme);
        }
        else {
            echo <<<TEXT
<h1>{$title}</h1>
<p>{$content}</p>
<p><a href="{$link}">返回</a></p>
TEXT;
        }
    }

    public function show_404() {
        header("HTTP/1.1 404 Not Found");
        header("status: 404 not found");
        if (Tpl::exist('/404', $this->theme)) {
            Tpl::display('/404', $this, $this->theme);
        }
        else {
            echo '<h1>Page not found</h1>';
        }
    }

    public function download_file($path, $filename, $fsencoding = '') {
        $full = File::conv_to($path, $fsencoding);
        if (!file_exists($full)) {
            $this->show_404();
        }
        self::send_download_header($filename);
        File::readfile($full);
    }

}

Controler::$cache_time = Config::get()->get_config('main', 'cache', 3600);
