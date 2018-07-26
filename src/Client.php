<?php

namespace Org\Snje\Minifw;

class Client {

    const CAROOT_URL = 'https://curl.haxx.se/ca/cacert.pem';
    const UPDATE_OFFSET = 604800; //7天

    protected $timeout = 30;
    protected $user_agent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0';
    protected $cookie = null;
    protected $referer = null;
    protected $header = null;
    protected $handle_cookie = true;
    protected $handle_referer = true;
    protected $caroot = null;

    public function __construct() {
        $caroot = Config::get()->get_config('path', 'caroot', '');
        if ($caroot == '') {
            return;
        }
        $caroot = WEB_ROOT . $caroot;
        if (file_exists($caroot)) {
            $this->caroot = $caroot;
        }
    }

    public function set_option($name, $value) {
        switch ($name) {
            case 'timeout':
                $value = intval($value);
                if ($value >= 0) {
                    $this->timeout = $value;
                }
                else {
                    throw new Exception('参数不合法');
                }
                break;
            case 'user_agent':
            case 'cookie':
            case 'referer':
            case 'header':
                $value = strval($value);
                if ($value !== '') {
                    $this->$name = $value;
                }
                else {
                    throw new Exception('参数不合法');
                }
                break;
            case 'handle_cookie':
            case 'handle_referer':
                if ($value !== true) {
                    $value = false;
                }
                $this->$name = $value;
                break;
            default :
                throw new Exception('名称非法');
        }
    }

    /**
     * 执行post操作
     * @param string $url 操作URL
     * @param mixed $data post数据
     * @param bool $encode 是否对post数据编码
     * @return array
     */
    public function post($url, $data = [], $encode = false) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        if (!empty($data)) {
            if (is_array($data) && $encode === true) {
                $data = http_build_query($data);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        return $this->send_request($ch);
    }

    /**
     * 执行get操作
     * @param string $url 操作URL
     * @param mixed $data 要发送的数据
     * @return array
     */
    public function get($url, $data = []) {
        if (!empty($data)) {
            if (is_array($data)) {
                $data = http_build_query($data);
            }
            if (strpos($url, '?') !== false) {
                $url .= '&' . $data;
            }
            else {
                $url .= '?' . $data;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        return $this->send_request($ch);
    }

    public function update_caroot() {
        $caroot = Config::get()->get_config('path', 'caroot', '');
        if ($caroot == '') {
            throw new Exception('路径未设置');
        }
        $caroot = WEB_ROOT . $caroot;

        $atime = 0;
        if (file_exists($caroot)) {
            $atime = fileatime($caroot);
        }
        if (time() - $atime > self::UPDATE_OFFSET) {
            $otime_out = $this->timeout;
            $this->timeout = 60;
            $ret = $this->get(self::CAROOT_URL);
            if ($ret['error'] === 0) {
                File::put_content($caroot, $ret['content']);
            }
            else {
                echo $ret['msg'];
            }
            $this->timeout = $otime_out;
        }
    }

    protected function send_request($ch) {
        $this->_parse_option($ch);

        $content = curl_exec($ch);
        $error = curl_errno($ch);

        if ($error != 0) {
            return [
                'error' => $error,
                'msg' => curl_error($ch),
            ];
        }

        $result = curl_getinfo($ch);
        $result['error'] = 0;

        curl_close($ch);

        if ($this->handle_referer) {
            $this->referer = $result['url'];
        }

        return $this->_parse_result($result, $content);
    }

    protected function _parse_option($ch) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); //不自动跳转
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($this->caroot !== null) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $this->caroot);
        }
        else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        if ($this->cookie !== null) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        }
        if ($this->referer !== null) {
            curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        }
        if ($this->user_agent !== null) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        }
        if ($this->header !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        }
    }

    protected function _parse_result($result, $content) {

        if (preg_match_all('/Set-Cookie:(.*);/iU', $content, $matches)) {
            $result['cookie'] = substr(implode(';', $matches[1]), 1);
        }
        else {
            $result['cookie'] = '';
        }

        $pos = strpos($content, "\r\n\r\n");
        if ($pos !== false) {
            $result['header'] = substr($content, 0, $pos);
            $result['content'] = substr($content, $pos + 4);
        }
        else {
            $result['header'] = '';
            $result['content'] = $content;
        }

        if ($this->handle_cookie && $result['cookie'] != '') {
            if ($this->cookie === null || $this->cookie === '') {
                $this->cookie = $result['cookie'];
            }
            else {
                $this->cookie = $this->cookie . ';' . $result['cookie'];
            }
        }

        return $result;
    }

}
