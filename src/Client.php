<?php

namespace Org\Snje\Minifw;

class Client {

    public $timeout = 30;
    public $user_agent = null;
    public $cookie = null;
    public $referer = null;
    public $header = null;
    public $handle_cookie = true;
    public $handle_referer = true;

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

        $this->_parse_option($ch);

        $content = curl_exec($ch);
        curl_close($ch);

        if ($this->handle_referer) {
            $this->referer = $url;
        }

        return $this->_parse_result($content);
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

        $this->_parse_option($ch);

        $content = curl_exec($ch);
        curl_close($ch);

        if ($this->handle_referer) {
            $this->referer = $url;
        }

        return $this->_parse_result($content);
    }

    protected function _parse_option($ch) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); //不自动跳转
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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

    protected function _parse_result($content) {
        $result = [];
        if (preg_match('/Location:(.*)\r\n/iU', $content, $matches)) {
            $result['location'] = trim($matches[1]);
        }
        else {
            $result['location'] = '';
        }

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
    }

}
