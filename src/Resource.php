<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class Resource {

    protected $map;
    protected $map_path;

    public function __construct($map_path = null) {
        if ($map_path === null) {
            $this->map_path = WEB_ROOT . Config::get()->get_config('main', 'resource_map');
        }
        else {
            $this->map_path = $map_path;
        }
        $this->load_map();
    }

    public function load_map() {
        if (file_exists($this->map_path)) {
            $this->map = require $this->map_path;
        }
    }

    public function compile_all() {
        foreach ($this->map as $cfg) {
            if ($cfg['type'] === 'file') {
                foreach ($cfg['map'] as $to => $from) {
                    if (!$this->compile($to)) {
                        return false;
                    }
                }
            }
            elseif ($cfg['type'] === 'dir') {
                foreach ($cfg['map'] as $to => $from) {
                    if (is_array($from)) {
                        $from = $from[0];
                    }
                    if (!$this->compile_dir($from, $to)) {
                        return false;
                    }
                }
            }
            else {
                return false;
            }
        }
        return true;
    }

    public function compile_dir($src, $dest) {
        $list = File::ls(WEB_ROOT . $src);
        foreach ($list as $file) {
            if ($file['dir'] === true) {
                if (!$this->compile_dir($src . '/' . $file['name'], $dest . '/' . $file['name'])) {
                    return false;
                }
            }
            else {
                if (!$this->compile($dest . '/' . $file['name'])) {
                    return false;
                }
            }
        }
        return true;
    }

    public function compile($dest) {
        $cfg = $this->get_match_rule($dest);
        if ($cfg === null) {
            return true;
        }
        if (!$this->need_compile($dest, $cfg)) {
            return true;
        }
        $func = 'compile_' . $cfg['method'];
        if (method_exists($this, $func)) {
            return $this->$func($dest, $cfg);
        }
        return false;
    }

    public function get_match_rule($dest) {
        foreach ($this->map as $cfg) {
            if ($cfg['type'] === 'file') {
                $ret = $this->match_file($dest, $cfg);
                if ($ret !== null) {
                    return $ret;
                }
            }
            elseif ($cfg['type'] === 'dir') {
                $ret = $this->match_dir($dest, $cfg);
                if ($ret !== null) {
                    return $ret;
                }
            }
        }
        return null;
    }

    protected function match_file($dest, $cfg) {
        if (isset($cfg['map'][$dest])) {
            if (!is_array($cfg['map'][$dest])) {
                $cfg['map'][$dest] = [
                    $cfg['map'][$dest]
                ];
            }
            $cfg['map'] = [
                $dest => $cfg['map'][$dest],
            ];
            return $cfg;
        }
        return null;
    }

    protected function match_dir($dest, $cfg) {
        if (isset($cfg['tail'])) {
            if (!is_array($cfg['tail'])) {
                $cfg['tail'] = [
                    $cfg['tail'],
                ];
            }
            $match = false;
            foreach ($cfg['tail'] as $v) {
                if (substr($dest, -1 * strlen($v)) === $v) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                return null;
            }
        }

        foreach ($cfg['map'] as $to => $from) {
            $len = strlen($to);
            if (strncmp($to, $dest, $len) !== 0) {
                continue;
            }
            if (is_array($from)) {
                $from = $from[0];
            }
            $cfg['map'] = [
                $dest => [
                    $from . substr($dest, $len)
                ],
            ];
            return $cfg;
        }
        return null;
    }

    public function need_compile($dest, $cfg) {
        if ($cfg === null) {
            return false;
        }
        if (!\file_exists(WEB_ROOT . $dest)) {
            return true;
        }
        $dtime = \filemtime(WEB_ROOT . $dest);
        foreach ($cfg['map'][$dest] as $file) {
            $full = WEB_ROOT . $file;
            if (\file_exists($full)) {
                $stime = \filemtime($full);
                if ($stime >= $dtime) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function compile_js($dest, $cfg) {
        $minifier = new \MatthiasMullie\Minify\JS();
        foreach ($cfg['map'][$dest] as $file) {
            $full = WEB_ROOT . $file;
            if (\file_exists($full)) {
                $minifier->add($full);
            }
        }
        $dest = WEB_ROOT . $dest;
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            \mkdir($dir, 0777, true);
        }
        $minifier->minify($dest);
        return true;
    }

    protected function compile_css($dest, $cfg) {
        $minifier = new \MatthiasMullie\Minify\CSS();
        foreach ($cfg['map'][$dest] as $file) {
            $full = WEB_ROOT . $file;
            if (\file_exists($full)) {
                $minifier->add($full);
            }
        }
        $dest = WEB_ROOT . $dest;
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            \mkdir($dir, 0777, true);
        }
        $str = $minifier->minify();
        file_put_contents($dest, $str);
        return true;
    }

    protected function compile_copy($dest, $cfg) {
        File::delete(WEB_ROOT . $dest, true);
        foreach ($cfg['map'][$dest] as $file) {
            $full = WEB_ROOT . $file;
            if (\file_exists($full)) {
                $content = \file_get_contents($full);
                File::put_content(WEB_ROOT . $dest, $content, '', FILE_APPEND);
            }
        }
        return true;
    }

}
