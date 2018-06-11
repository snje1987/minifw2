<?php

namespace Org\Snje\Minifw;

class TableUtils {

    public static function apply_all_diff($ns = '', $path = '') {
        if ($path == '' || !is_dir($path)) {
            return;
        }
        $dir = opendir($path);
        while (false !== ($file = readdir($dir))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($path . '/' . $file)) {
                self::apply_all_diff($ns . '\\' . $file, $path . '/' . $file);
            }
            else {
                if (substr($file, -4, 4) !== '.php') {
                    continue;
                }
                $classname = $ns . '\\' . substr($file, 0, strlen($file) - 4);
                if (class_exists($classname) && is_callable($classname . '::get')) {
                    $obj = $classname::get();
                    if ($obj instanceof Table) {
                        $table_diff = $obj->table_diff();
                        if (empty($table_diff)) {
                            continue;
                        }
                        $db = $obj->get_db();
                        foreach ($table_diff as $diff) {
                            $db->query($diff['trans']);
                        }
                    }
                }
            }
        }
        closedir($dir);
    }

    public static function display_all_diff($ns = '', $path = '') {
        $diff = self::get_all_diff($ns, $path);
        if (!headers_sent()) {
            header("Content-Type:text/plain;charset=utf-8");
        }
        $otable = '';
        $trans = [];
        foreach ($diff as $class => $info) {
            echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++\n";
            echo $class . ' ' . $info['tbname'] . "\n\n";
            foreach ($info['diff'] as $line) {
                echo $line['diff'] . "\n";
                if ($line['trans'] !== null) {
                    $trans[] = $line['trans'];
                }
            }
        }
        echo "\n\n================================================================\n\n";
        echo implode("\n", $trans) . "\n";
    }

    public static function get_all_diff($ns = '', $path = '') {
        if ($path == '' || !is_dir($path)) {
            return;
        }
        $diff = [];
        try {
            $dir = opendir($path);
            while (false !== ($file = readdir($dir))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $ndiff = [];
                if (is_dir($path . '/' . $file)) {
                    $ndiff = self::get_all_diff($ns . '\\' . $file, $path . '/' . $file);
                }
                else {
                    if (substr($file, -4, 4) !== '.php') {
                        continue;
                    }
                    $classname = $ns . '\\' . substr($file, 0, strlen($file) - 4);
                    if (class_exists($classname) && is_callable($classname . '::get')) {
                        $obj = $classname::get();
                        if ($obj instanceof Table) {
                            $table_diff = $obj->table_diff();
                            if (empty($table_diff)) {
                                continue;
                            }
                            $ndiff[$classname] = [
                                'tbname' => $classname::$tbname,
                                'diff' => $table_diff
                            ];
                        }
                    }
                }
                if (empty($ndiff)) {
                    continue;
                }
                $diff = array_merge($diff, $ndiff);
            }
            closedir($dir);
            return $diff;
        }
        catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }

}
