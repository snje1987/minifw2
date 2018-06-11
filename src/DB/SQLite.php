<?php

namespace Org\Snje\Minifw\DB;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class SQLite extends FW\DB {

    protected $_sqlite;
    protected $_rollback = false;

    protected function __construct($args = []) {
        parent::__construct();
        $config = FW\Config::get();
        $ini = $config->get_config('sqlite');
        if (!empty($args)) {
            $ini['path'] = isset($args['path']) ? strval($args['path']) : $ini['path'];
        }

        if (empty($ini)) {
            throw new Exception('数据库未配置');
        }
        $this->_sqlite = new \SQLite3(WEB_ROOT . $ini['path'], SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    }

    public function last_insert_id() {
        return $this->_sqlite->lastInsertRowID();
    }

    public function last_error() {
        return $this->_sqlite->lastErrorMsg();
    }

    public function query($sql, $var = []) {
        $ret = $this->_sqlite->query($sql);
        if ($ret === false && DEBUG == 1) {
            throw new Exception($this->last_error() . "\n" . $sql);
        }
        return $ret;
    }

    public function fetch_all($res) {
        for ($data = []; $tmp = $res->fetchArray(MYSQLI_ASSOC);) {
            $data[] = $tmp;
        }
        return $data;
    }

    public function fetch_hash($res) {
        throw new Exception('方法未实现');
    }

    public function fetch($res) {
        return $res->fetchArray(MYSQLI_ASSOC);
    }

    public function free($res) {
        return $res->finalize();
    }

    public function parse_str($str) {
        $str = htmlspecialchars(trim($str));
        $str = str_replace('\'', '\'\'', $str);
        return $str;
    }

    public function parse_richstr($str) {
        $str = str_replace('\'', '\'\'', $str);
        return trim($str);
    }

    public function parse_like($str) {
        $str = str_replace(
                ['/', '\'', '"', '[', ']', '%', '&', '_', '(', ')']
                , ['//', '\'\'', '""', '/[', '/]', '/%', '/&', '/_', '/(', '/)'], $str
        );
        return trim($str);
    }

    public function multi_query($sql) {
        return $this->_sqlite->query($sql);
    }

    protected function _begin() {
        $this->query('begin');
        $this->_rollback = true;
    }

    protected function _commit() {
        if ($this->_rollback) {
            $this->query('COMMIT');
            $this->_rollback = false;
        }
    }

    protected function _rollback() {
        if ($this->_rollback) {
            $this->query('ROLLBACK');
            $this->_rollback = false;
        }
    }

    public function get_table_field($tbname) {
        throw new Exception('方法未实现');
    }

    public function get_table_index($tbname) {
        throw new Exception('方法未实现');
    }

    public function get_table_status($tbname) {
        throw new Exception('方法未实现');
    }

    public static function create_table_sql($tbname, $tbinfo, $field, $index) {
        throw new Exception('方法未实现');
    }

    public static function get_field_diff($tbname, $from, $to) {
        throw new Exception('方法未实现');
    }

    public static function get_index_diff($tbname, $from, $to, $removed) {
        throw new Exception('方法未实现');
    }

    public static function get_status_diff($tbname, $from, $to) {
        throw new Exception('方法未实现');
    }

    public static function drop_table_sql($tbname) {
        throw new Exception('方法未实现');
    }

}
