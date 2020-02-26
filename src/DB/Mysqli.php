<?php

namespace Org\Snje\Minifw\DB;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class Mysqli extends FW\DB {

    /**
     * @var \mysqli
     */
    protected $_mysqli;
    protected $_encoding;
    protected $_host;
    protected $_username;
    protected $_password;
    protected $_dbname;
    protected $_explain_log;
    protected $_explain_level;
    protected $_rollback = false;

    const DEFAULT_ENGINE = 'InnoDB';
    const DEFAULT_CHARSET = 'utf8';

    public static $explain_type = [
        'system' => 2,
        'const' => 2,
        'eq_ref' => 2,
        'ref' => 2,
        'fulltext' => 2,
        'ref_or_null' => 1,
        'unique_subquery' => 1,
        'index_subquery' => 1,
        'range' => 2,
        'index_merge' => 0,
        'index' => 0,
        'ALL' => 0,
    ];

    protected function __construct($args = []) {
        parent::__construct();
        $config = FW\Config::get();
        $ini = $config->get_config('mysql');
        $ini = array_merge($ini, $args);

        if (empty($ini)) {
            throw new Exception('数据库未配置');
        }
        $this->_host = isset($ini['host']) ? strval($ini['host']) : '';
        $this->_username = isset($ini['username']) ? strval($ini['username']) : '';
        $this->_password = isset($ini['password']) ? strval($ini['password']) : '';
        $this->_dbname = isset($ini['dbname']) ? strval($ini['dbname']) : '';
        $this->_encoding = isset($ini['encoding']) ? strval($ini['encoding']) : '';
        $this->_explain_log = isset($ini['explain_log']) ? strval($ini['explain_log']) : null;
        $this->_explain_level = isset($ini['explain_level']) ? $ini['explain_level'] : -1;
        if (!is_array($this->_explain_level)) {
            $this->_explain_level = intval($this->_explain_level);
        }
        $this->_mysqli = new \mysqli($this->_host, $this->_username, $this->_password, $this->_dbname);
        if ($this->_mysqli->connect_error) {
            throw new Exception('数据库连接失败');
        }
        if (!$this->_mysqli->set_charset($this->_encoding)) {
            throw new Exception('数据库查询失败');
        }
    }

    public function last_insert_id() {
        return $this->_mysqli->insert_id;
    }

    public function last_error() {
        return $this->_mysqli->error;
    }

    public function query($sql, $var = []) {
        if (!$this->_mysqli->ping()) {
            @$this->_mysqli->close();
            $this->_mysqli = new mysqli($this->_host, $this->_username, $this->_password, $this->_dbname);
            if ($this->_mysqli->connect_error) {
                throw new Exception('数据库连接失败');
            }
            if (!$this->_mysqli->set_charset($this->_encoding)) {
                throw new Exception('数据库查询失败');
            }
        }
        $sql = $this->compile_sql($sql, $var);
        if ($this->_explain_level > -1) {
            $this->log_explain($sql);
        }
        $ret = $this->_mysqli->query($sql);
        if ($ret === false) {
            if (DEBUG == 1) {
                throw new Exception($this->last_error() . "\n" . $sql);
            }
            else {
                throw new Exception('数据库查询失败');
            }
        }
        return $ret;
    }

    public function log_explain($sql) {
        if ($this->_explain_log === null) {
            return;
        }

        $sql = 'explain ' . $sql;
        $result = $this->_mysqli->query($sql);
        if ($result === false) {
            return;
        }
        $data = $this->fetch_all($result);
        $this->free($result);

        $log_str = $sql . "\n";
        $need_log = false;
        foreach ($data as $v) {
            $log_str .= print_r($v, true);
            if (is_array($this->_explain_level)) {
                if (!isset($this->_explain_level[$v['type']]) || $this->_explain_level[$v['type']] === false) {
                    $need_log = true;
                }
            }
            else {
                if (!array_key_exists($v['type'], self::$explain_type) || self::$explain_type[$v['type']] <= $this->_explain_level) {
                    $need_log = true;
                }
            }
        }
        if ($need_log) {
            FW\File::put_content(WEB_ROOT . $this->_explain_log, $log_str, '', FILE_APPEND);
        }
    }

    public function fetch_all($res) {
        if (method_exists('mysqli_result', 'fetch_all')) {
            $data = $res->fetch_all(MYSQLI_ASSOC);
        }
        else {
            for ($data = []; $tmp = $res->fetch_array(MYSQLI_ASSOC);) {
                $data[] = $tmp;
            }
        }
        return $data;
    }

    /**
     * @param \mysqli_result $res
     * @return array
     */
    public function fetch_hash($res) {
        $cols = $res->fetch_fields();
        $count = count($cols);
        if ($count <= 1) {
            return [];
        }
        $key = $cols[0]->name;
        $value = $cols[1]->name;
        for ($data = []; $tmp = $res->fetch_array(MYSQLI_ASSOC);) {
            if ($count == 2) {
                $data[$tmp[$key]] = $tmp[$value];
            }
            else {
                $data[$tmp[$key]] = $tmp;
            }
        }
        return $data;
    }

    public function fetch($res) {
        return $res->fetch_array(MYSQLI_ASSOC);
    }

    public function free($res) {
        return $res->free();
    }

    public function parse_str($str) {
        $str = htmlspecialchars(trim($str));
        $str = $this->_mysqli->escape_string($str);
        return $str;
    }

    public function parse_richstr($str) {
        $str = $this->_mysqli->escape_string($str);
        return trim($str);
    }

    public function parse_like($str) {
        $str = $this->_mysqli->escape_string($str);
        $str = str_replace('_', '\_', $str);
        $str = str_replace('%', '\%', $str);
        return trim($str);
    }

    public function multi_query($sql) {
        return $this->_mysqli->multi_query($sql);
    }

    protected function _begin() {
        $this->query('SET AUTOCOMMIT=0');
        $this->query('BEGIN');
        $this->_rollback = true;
    }

    protected function _commit() {
        if ($this->_rollback) {
            $this->query('COMMIT');
            $this->query('SET AUTOCOMMIT=1');
            $this->_rollback = false;
        }
    }

    protected function _rollback() {
        if ($this->_rollback) {
            $this->query('ROLLBACK');
            $this->query('SET AUTOCOMMIT=1');
            $this->_rollback = false;
        }
    }

    public function get_table_field($tbname) {
        $sql = 'SHOW FULL FIELDS FROM `' . $tbname . '`';
        $data = $this->get_query($sql);
        if ($data === false) {
            throw new Exception('数据表不存在:' . $tbname);
        }
        $fields = [];
        foreach ($data as $k => $v) {
            $fields[$v['Field']] = [
                'no' => $k,
                'type' => $v['Type'],
                'null' => $v['Null'],
                'extra' => $v['Extra'],
                'default' => $v['Default'],
                'comment' => $v['Comment'],
            ];
        }
        return $fields;
    }

    public function get_table_index($tbname) {
        $sql = 'SHOW INDEX FROM `' . $tbname . '`';
        $data = $this->get_query($sql);
        if ($data === false) {
            throw new Exception('数据表不存在:' . $tbname);
        }
        $index = [];
        foreach ($data as $v) {
            $name = $v['Key_name'];
            if (!isset($index[$name])) {
                $index[$name] = [
                    'fields' => [
                        $v['Column_name']
                    ]
                ];
                if (isset($v['Index_comment'])) {
                    $index[$name]['comment'] = $v['Index_comment'];
                }
                if ($name !== 'PRIMARY') {
                    if ($v['Non_unique'] == 0) {
                        $index[$name]['unique'] = true;
                    }
                    elseif ($v['Index_type'] == 'FULLTEXT') {
                        $index[$name]['fulltext'] = true;
                    }
                }
            }
            else {
                $index[$name]['fields'][] = $v['Column_name'];
            }
        }
        return $index;
    }

    public function get_table_status($tbname) {
        $sql = 'SHOW CREATE TABLE `' . $tbname . '`';
        $data = $this->get_query($sql);
        if ($data === false || count($data) !== 1) {
            throw new Exception('数据表不存在:' . $tbname);
        }
        $create_sql = $data[0]['Create Table'];
        $matches = [];
        if (preg_match('/ENGINE=(\w+)( AUTO_INCREMENT=\d+)? DEFAULT CHARSET=(\w+)( ROW_FORMAT=\w+)?( COMMENT=\'([^\']*)\')?$/', $create_sql, $matches)) {
            $ret = [
                'engine' => $matches[1],
                'charset' => $matches[3],
                'comment' => '',
            ];
            if (isset($matches[6]) && $matches[6] != '') {
                $ret['comment'] = $matches[6];
            }
            return $ret;
        }
        throw new Exception('返回信息处理失败');
    }

    public function compile_sql($sql, $var = []) {
        foreach ($var as $k => $v) {
            if (is_array($v)) {
                switch ($v[0]) {
                    case 'expr':
                        $sql = str_replace("{{$k}}", $v[1], $sql);
                        break;
                    case 'rich':
                        $v[1] = $this->parse_richstr($v[1]);
                        $sql = str_replace("{{$k}}", "'{$v[1]}'", $sql);
                        break;
                    case 'like':
                        $v[1] = $this->parse_like($v[1]);
                        $sql = str_replace("{{$k}}", "{$v[1]}", $sql);
                        break;
                    default :
                        $v[1] = $this->parse_str($v[1]);
                        $sql = str_replace("{{$k}}", "'{$v[1]}'", $sql);
                }
            }
            else {
                $v = $this->parse_str($v);
                $sql = str_replace("{{$k}}", "'{$v}'", $sql);
            }
        }
        return $sql;
    }

    public static function create_table_sql($tbname, $tbinfo, $field, $index, $dim = '') {
        $engine = isset($tbinfo['engine']) ? $tbinfo['engine'] : self::DEFAULT_ENGINE;
        $charset = isset($tbinfo['charset']) ? $tbinfo['charset'] : self::DEFAULT_CHARSET;
        $comment = isset($tbinfo['comment']) ? $tbinfo['comment'] : '';

        if ($tbname === '' || $engine === '' || $charset == '') {
            throw new Exception('参数错误');
        }

        $sql = 'CREATE TABLE IF NOT EXISTS `' . $tbname . '` (' . $dim;
        $lines = [];
        foreach ($field as $k => $v) {
            $sql_info = self::field_to_sql($k, $v);
            $lines[] = $sql_info['sql'];
        }

        foreach ($index as $k => $v) {
            $lines[] = self::index_to_sql($k, $v);
        }

        $sql .= implode(',' . $dim, $lines) . $dim;
        $sql .= ') ENGINE=' . $engine . ' DEFAULT CHARSET=' . $charset;
        if ($comment != '') {
            $sql .= ' COMMENT=\'' . $comment . '\'';
        }
        return $sql;
    }

    private static function move_field_no(&$fields, $from, $to = -1, $offset = 1) {
        foreach ($fields as $k => $v) {
            if ($v['no'] >= $from && ($to < 0 || $v['no'] < $to)) {
                $fields[$k]['no'] += $offset;
            }
        }
    }

    public static function get_field_diff($tbname, $from, $to) {
        $diff = [];
        $last = [];
        $tail = ' first';
        $i = 0;

        //计算增加的列
        foreach ($to as $k => $v) {
            $to_sql = self::field_to_sql($k, $v);
            if (!isset($from[$k])) {
                if (isset($to_sql['sql_first'])) {
                    $diff[] = [
                        'diff' => '+[' . $i . '] ' . $to_sql['sql_first'],
                        'trans' => 'ALTER TABLE `' . $tbname . '` ADD ' . $to_sql['sql_first'] . $tail . ';',
                    ];
                    $last[] = [
                        'diff' => '-[' . $i . '] ' . $to_sql['sql_first'] . "\n" . '+[' . $i . '] ' . $to_sql['sql'],
                        'trans' => 'ALTER TABLE `' . $tbname . '` CHANGE `' . $k . '` ' . $to_sql['sql'] . $tail . ';',
                    ];
                }
                else {
                    $diff[] = [
                        'diff' => '+[' . $i . '] ' . $to_sql['sql'],
                        'trans' => 'ALTER TABLE `' . $tbname . '` ADD ' . $to_sql['sql'] . $tail . ';',
                    ];
                }
                self::move_field_no($from, $i);
            }
            $tail = ' after `' . $k . '`';
            $i ++;
        }

        //计算删除的列
        $i = 0;
        $removed = [];
        foreach ($from as $k => $v) {
            $i ++;
            if (array_key_exists($k, $to)) {
                continue;
            }
            $from_sql = self::field_to_sql($k, $v);
            $diff[] = [
                'diff' => '- ' . $from_sql['sql'],
                'trans' => 'ALTER TABLE `' . $tbname . '` DROP `' . $k . '`;',
            ];
            self::move_field_no($from, $i - 1, -1, -1);
            $removed[] = $k;
        }

        //计算变化的列
        $i = 0;
        $tail = ' first';
        foreach ($to as $k => $v) {
            if (isset($from[$k])) {
                $to_sql = self::field_to_sql($k, $v);
                $from_sql = self::field_to_sql($k, $from[$k]);
                if ($from_sql['sql'] != $to_sql['sql'] || $i != $from[$k]['no']) {
                    //如果原定义不包含自增而新定义包含
                    if (isset($to_sql['sql_first']) && !isset($from_sql['sql_first'])) {
                        $diff[] = [
                            'diff' => '-[' . $from[$k]['no'] . '] ' . $from_sql['sql'] . "\n" . '+[' . $i . '] ' . $to_sql['sql_first'],
                            'trans' => 'ALTER TABLE `' . $tbname . '` CHANGE `' . $k . '` ' . $to_sql['sql_first'] . $tail . ';',
                        ];
                        $last[] = [
                            'diff' => '-[' . $from[$k]['no'] . '] ' . $to_sql['sql_first'] . "\n" . '+[' . $i . '] ' . $to_sql['sql'],
                            'trans' => 'ALTER TABLE `' . $tbname . '` CHANGE `' . $k . '` ' . $to_sql['sql'] . $tail . ';',
                        ];
                    }
                    else {
                        $diff[] = [
                            'diff' => '-[' . $from[$k]['no'] . '] ' . $from_sql['sql'] . "\n" . '+[' . $i . '] ' . $to_sql['sql'],
                            'trans' => 'ALTER TABLE `' . $tbname . '` CHANGE `' . $k . '` ' . $to_sql['sql'] . $tail . ';',
                        ];
                    }
                }
                self::move_field_no($from, $i, $from[$k]['no']);
            }
            $tail = ' after `' . $k . '`';
            $i ++;
        }

        return [$diff, $removed, $last];
    }

    public static function get_index_diff($tbname, $from, $to, $removed) {
        $diff = [];
        foreach ($to as $k => $v) {
            $to_sql = self::index_to_sql($k, $v, false);
            if (!isset($from[$k])) {
                $diff[] = [
                    'diff' => '+ ' . $to_sql,
                    'trans' => 'ALTER TABLE `' . $tbname . '` ADD ' . $to_sql . ';',
                ];
                continue;
            }
            $from_sql = self::index_to_sql($k, $from[$k], false);
            if ($from_sql != $to_sql) {
                $trans = 'ALTER TABLE `' . $tbname . '` DROP';
                if ($k == 'PRIMARY') {
                    $trans .= ' PRIMARY KEY';
                }
                else {
                    $trans .= ' INDEX `' . $k . '`';
                }
                $trans .= ', ADD ' . $to_sql . ';';
                $diff[] = [
                    'diff' => '- ' . $from_sql . "\n" . '+ ' . $to_sql,
                    'trans' => $trans,
                ];
                continue;
            }
        }

        foreach ($from as $k => $v) {
            if (array_key_exists($k, $to)) {
                continue;
            }
            $has_removed = true;
            foreach ($v['fields'] as $field) {
                if (!in_array($field, $removed)) {
                    $has_removed = false;
                    break;
                }
            }
            $from_sql = self::index_to_sql($k, $v, false);
            $trans = 'ALTER TABLE `' . $tbname . '` DROP INDEX `' . $k . '`;';
            if ($k == 'PRIMARY') {
                $trans = 'ALTER TABLE `' . $tbname . '` DROP PRIMARY KEY;';
            }

            if ($has_removed) {
                //如果索引中所有的列已经被删除，索引会被自动删除，不必生成删除语句
                $trans = null;
            }

            $diff[] = [
                'diff' => '- ' . $from_sql,
                'trans' => $trans,
            ];
        }

        return $diff;
    }

    public static function get_status_diff($tbname, $from, $to) {
        $diff = [];
        if ($from['engine'] != $to['engine']) {
            $diff[] = [
                'diff' => '- Engine=' . $from['engine'] . "\n" . '+ Engine=' . $to['engine'],
                'trans' => 'ALTER TABLE `' . $tbname . '` ENGINE=' . $to['engine'] . ';',
            ];
        }
        if ($from['comment'] != $to['comment']) {
            $diff[] = [
                'diff' => '- Comment=\'' . $from['comment'] . "'\n" . '+ Comment=\'' . $to['comment'] . '\'',
                'trans' => 'ALTER TABLE `' . $tbname . '` COMMENT=\'' . $to['comment'] . '\';',
            ];
        }
        if ($from['charset'] != $to['charset']) {
            $diff[] = [
                'diff' => '- Charset=\'' . $from['charset'] . "'\n" . '+ Charset=\'' . $to['charset'] . '\'',
                'trans' => 'ALTER TABLE `' . $tbname . '` DEFAULT CHARSET=\'' . $to['charset'] . '\';',
            ];
        }
        return $diff;
    }

    protected static function field_to_sql($name, $attr) {
        $info = [
            'sql' => '',
        ];
        if (isset($attr['extra']) && $attr['extra'] === 'auto_increment') {
            $info['sql_first'] = '';
        }
        switch ($attr['type']) {
            case 'text':
                $info['sql'] = '`' . $name . '` text';
                if (!isset($attr['null']) || $attr['null'] === 'NO') {
                    $info['sql'] .= ' NOT NULL';
                }
                break;
            default :
                $info['sql'] = '`' . $name . '` ' . $attr['type'];
                if (!isset($attr['null']) || $attr['null'] === 'NO') {
                    $info['sql'] .= ' NOT NULL';
                }
                if (isset($info['sql_first'])) {
                    $info['sql_first'] = $info['sql'];
                }
                if (isset($attr['extra']) && $attr['extra'] !== null && $attr['extra'] !== '') {
                    $info['sql'] .= ' ' . $attr['extra'];
                }
                if (isset($attr['default']) && $attr['default'] !== null) {
                    $tmp = ' DEFAULT \'' . str_replace('\'', '\'\'', $attr['default']) . '\'';
                    $info['sql'] .= $tmp;
                    if (isset($info['sql_first'])) {
                        $info['sql_first'] .= $tmp;
                    }
                }
                break;
        }
        if (isset($attr['comment']) && $attr['comment'] !== null) {
            $tmp = ' COMMENT \'' . str_replace('\'', '\'\'', $attr['comment']) . '\'';
            $info['sql'] .= $tmp;
            if (isset($info['sql_first'])) {
                $info['sql_first'] .= $tmp;
            }
        }
        return $info;
    }

    protected static function index_to_sql($name, $attr, $in_create = true) {
        $sql = '';
        switch ($name) {
            case 'PRIMARY':
                $sql = 'PRIMARY KEY (`' . implode('`,`', $attr['fields']) . '`)';
                break;
            default :
                if ($in_create) {
                    if (isset($attr['unique']) && $attr['unique'] === true) {
                        $sql = 'UNIQUE ';
                    }
                    else if (isset($attr['fulltext']) && $attr['fulltext'] === true) {
                        $sql = 'FULLTEXT ';
                    }
                    $sql .= 'KEY ';
                }
                else {
                    if (isset($attr['unique']) && $attr['unique'] === true) {
                        $sql = 'UNIQUE ';
                    }
                    elseif (isset($attr['fulltext']) && $attr['fulltext'] === true) {
                        $sql = 'FULLTEXT ';
                    }
                    else {
                        $sql = 'INDEX ';
                    }
                }
                $sql .= '`' . $name . '` (`' . implode('`,`', $attr['fields']) . '`)';
                break;
        }
        if (isset($attr['comment']) && $attr['comment'] != '') {
            $sql .= ' COMMENT \'' . str_replace('\'', '\'\'', $attr['comment']) . '\'';
        }
        return $sql;
    }

    public static function drop_table_sql($tbname) {
        return 'DROP TABLE IF EXISTS `' . $tbname . '`';
    }

}
