<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

abstract class DB implements TableAnalysis {

    protected static $_instance = [];

    /**
     * 获取实例
     * @return static 实例
     */
    public static function get($args = [], $id = '') {
        if (!isset(static::$_instance[static::class])) {
            static::$_instance[static::class] = [];
        }
        if (!isset(static::$_instance[static::class][$id])) {
            static::$_instance[static::class][$id] = new static($args);
        }
        return static::$_instance[static::class][$id];
    }

    private $_transaction_lv = 0;

    public static function get_default($args = [], $id = '') {
        $type = '';
        if (isset($args['type'])) {
            $type = strval($args['type']);
        }
        if ($type == '') {
            $type = FW\Config::get()->get_config('main', 'db', '');
        }
        if ($type == '') {
            throw new Exception('未指定数据库类型');
        }
        $class_name = __NAMESPACE__ . '\\DB\\' . $type;
        if (!class_exists($class_name)) {
            throw new Exception('类型不存在');
        }
        return $class_name::get($args, $id);
    }

    public function limit_query($tbname, $condition = [], $field = [], $lock = false) {
        $fieldstr = $this->_parse_field($field);
        $conditionstr = $this->_parse_condition($condition, $tbname);

        $sql = 'select ' . $fieldstr . ' from `' . $tbname . '`' . $conditionstr;
        if ($lock === true && $this->_transaction_lv > 0) {
            $sql .= ' for update';
        }

        $res = $this->query($sql);
        if ($res === false) {
            return false;
        }
        $data = $this->fetch_all($res);
        $this->free($res);
        return $data;
    }

    public function get_query($sql, $var = [], $hash = false) {
        $res = $this->query($sql, $var);
        if ($res === false) {
            return false;
        }
        if ($hash) {
            $data = $this->fetch_hash($res);
        }
        else {
            $data = $this->fetch_all($res);
        }
        $this->free($res);
        return $data;
    }

    public function one_query($tbname, $condition = [], $field = [], $lock = false) {
        $fieldstr = $this->_parse_field($field);
        $conditionstr = $this->_parse_condition($condition, $tbname);
        $sql = 'select ' . $fieldstr . ' from `' . $tbname . '`' . $conditionstr . ' limit 1';
        if ($lock === true && $this->_transaction_lv > 0) {
            $sql .= ' for update';
        }
        $res = $this->query($sql);
        if ($res === false) {
            return false;
        }
        $data = $this->fetch($res);
        $this->free($res);
        return $data;
    }

    public function hash_query($tbname, $condition = [], $field = [], $lock = false) {
        $fieldstr = $this->_parse_field($field);
        $conditionstr = $this->_parse_condition($condition, $tbname);

        $sql = 'select ' . $fieldstr . ' from `' . $tbname . '`' . $conditionstr;
        if ($lock === true && $this->_transaction_lv > 0) {
            $sql .= ' for update';
        }

        $res = $this->query($sql);
        if ($res === false) {
            return false;
        }
        $data = $this->fetch_hash($res);
        $this->free($res);
        return $data;
    }

    public function count($tbname, $condition = []) {
        $conditionstr = $this->_parse_condition($condition);
        $sql = 'select count(*) as \'count\' from `' . $tbname . '` ' . $conditionstr;
        $res = $this->query($sql);
        if ($res === false) {
            return false;
        }
        $data = $this->fetch($res);
        $this->free($res);
        return $data['count'];
    }

    public function insert($tbname, $value) {
        $valuestr = $this->_parse_value($value);
        $sql = 'insert into `' . $tbname . '`' . $valuestr;
        return $this->query($sql);
    }

    public function replace($tbname, $value) {
        $valuestr = $this->_parse_value($value);
        $sql = 'replace into `' . $tbname . '`' . $valuestr;
        return $this->query($sql);
    }

    public function delete($tbname, $condition = []) {
        if (empty($condition)) {
            throw new Exception('删除条件不能为空');
        }
        $conditionstr = $this->_parse_condition($condition);
        $sql = 'delete from `' . $tbname . '`' . $conditionstr;
        return $this->query($sql);
    }

    public function update($tbname, $value, $condition = []) {
        if (empty($condition)) {
            throw new Exception('更新条件不能为空');
        }
        $updatestr = $this->_parse_update($value);
        $conditionstr = $this->_parse_condition($condition);
        $sql = 'update `' . $tbname . '` set ' . $updatestr . $conditionstr;
        return $this->query($sql);
    }

    public function begin() {
        ++$this->_transaction_lv;
        if ($this->_transaction_lv == 1) {
            $this->_begin();
        }
    }

    public function commit() {
        --$this->_transaction_lv;
        if ($this->_transaction_lv <= 0) {
            $this->_transaction_lv = 0;
            $this->_commit();
        }
    }

    public function rollback() {
        --$this->_transaction_lv;
        if ($this->_transaction_lv <= 0) {
            $this->_transaction_lv = 0;
            $this->_rollback();
        }
    }

    protected function __construct($args = []) {

    }

    protected function _parse_field($field) {
        if (empty($field)) {
            return '*';
        }
        $arr = [];
        foreach ($field as $k => $v) {
            if (is_int($k)) {
                $arr[] = '`' . $v . '`';
            }
            else {
                $arr[] = $v . ' as \'' . $k . '\'';
            }
        }
        return implode(',', $arr);
    }

    protected function _parse_value($value) {
        if (empty($value)) {
            throw new Exception('参数错误');
        }
        $farr = [];
        $varr = [];

        foreach ($value as $k => $v) {
            $farr[] = $k;
            if (is_array($v)) {
                if ($v[0] == 'rich') {
                    $varr[] = '\'' . $this->parse_richstr(strval($v[1])) . '\'';
                }
                elseif ($v[0] == 'expr') {
                    $varr[] = $v[1];
                }
                else {
                    throw new Exception('参数错误');
                }
            }
            else {
                $varr[] = '\'' . $this->parse_str(strval($v)) . '\'';
            }
        }
        return '(`' . implode('`,`', $farr) . '`) values (' . implode(',', $varr) . ')';
    }

    protected function _parse_update($value) {

        $arr = [];

        foreach ($value as $k => $v) {
            if (is_array($v)) {
                if ($v[0] == 'rich') {
                    $arr[] = '`' . $k . '`=\'' . $this->parse_richstr(strval($v[1])) . '\'';
                }
                elseif ($v[0] == 'expr') {
                    $arr[] = '`' . $k . '`=' . $v[1];
                }
                else {
                    throw new Exception('参数错误');
                }
            }
            else {
                $arr[] = '`' . $k . '`=\'' . $this->parse_str(strval($v)) . '\'';
            }
        }

        return implode(',', $arr);
    }

    protected function _parse_opt($value, &$first, $key, $tbname) {
        $str = '';
        $value[0] = strval($value[0]);
        switch ($value[0]) {
            case '>':
            case '<':
            case '=':
            case '>=':
            case '<=':
            case '<>':
                if ($first != true) {
                    $str .= ' and ';
                }
                else {
                    $first = false;
                }
                $str .= ' (' . ($tbname == '' ? '' : '`' . $tbname . '`.') . '`' . $key . '`' . $value[0] . '\'' . ($this->parse_str($value[1])) . '\')';
                break;
            case 'between':
                if ($first != true) {
                    $str .= ' and ';
                }
                else {
                    $first = false;
                }
                $str .= ' (' . ($tbname == '' ? '' : '`' . $tbname . '`.') . '`' . $key . '` between \'' . ($this->parse_str($value[1])) . '\' and \'' . ($this->parse_str($value[2])) . '\')';
                break;
            case 'have':
                if ($first != true) {
                    $str .= ' and ';
                }
                else {
                    $first = false;
                }
                $str .= ' (' . ($tbname == '' ? '' : '`' . $tbname . '`.') . '`' . $key . '` like \'%' . ($this->parse_like($value[1])) . '%\')';
                break;
            case 'end':
                if ($first != true) {
                    $str .= ' and ';
                }
                else {
                    $first = false;
                }
                $str .= ' (' . ($tbname == '' ? '' : '`' . $tbname . '`.') . '`' . $key . '` like \'%' . ($this->parse_like($value[1])) . '\')';
                break;
            case 'begin':
                if ($first != true) {
                    $str .= ' and ';
                }
                else {
                    $first = false;
                }
                $str .= ' (' . ($tbname == '' ? '' : '`' . $tbname . '`.') . '`' . $key . '` like \'' . ($this->parse_like($value[1])) . '%\')';
                break;
            case 'nohave':
                if ($first != true) {
                    $str .= ' and ';
                }
                else {
                    $first = false;
                }
                $str .= ' (' . ($tbname == '' ? '' : '`' . $tbname . '`.') . '`' . $key . '` not like \'%' . ($this->parse_like($value[1])) . '%\')';
                break;
            case 'in':
                if ($first != true) {
                    $str .= ' and ';
                }
                else {
                    $first = false;
                }

                if (is_array($value[1])) {
                    $str .= ' (' . ($tbname == '' ? '' : '`' . $tbname . '`.') . '`' . $key . '` in (\'' . implode('\',\'', $value[1]) . '\'))';
                }
                else {
                    $str .= ' (' . ($tbname == '' ? '' : '`' . $tbname . '`.') . '`' . $key . '` in (';
                    $str .= 'select `' . ($this->parse_str($value[2])) . '` from `' . ($this->parse_str($value[1])) . '`' . $this->_parse_condition($value[3], $this->parse_str($value[1]));
                    $str .= '))';
                }
                break;
            case 'or':
                $tmp_first = true;
                $first = false;
                $str .= ' or (';
                $str .= $this->_parse_opt($value[1], $tmp_first, $key, $tbname);
                $str .= ')';
                break;
            default:
                throw new Exception('查询条件错误');
        }
        return $str;
    }

    protected function _parse_condition($condition, $tbname = '') {
        if (empty($condition)) {
            return '';
        }
        $str = '';
        $first = true;
        foreach ($condition as $key => $value) {
            if ($key == 'order') {
                $str .= ' order by ' . $value;
            }
            elseif ($key == 'limit') {
                $str .= ' limit ' . $value;
            }
            else {
                if (is_array($value)) {
                    if (is_array($value[0])) {
                        foreach ($value as $one) {
                            $str .= $this->_parse_opt($one, $first, $key, $tbname);
                        }
                    }
                    else {
                        $str .= $this->_parse_opt($value, $first, $key, $tbname);
                    }
                }
                else {
                    if ($first != true) {
                        $str .= ' and ';
                    }
                    else {
                        $first = false;
                    }
                    if ($tbname != '') {
                        $str .= '`' . $tbname . '` .';
                    }
                    $str .= ' `' . $key . '` = \'' . ($this->parse_str(strval($value))) . '\'';
                }
            }
        }
        if ($first == false) {
            $str = ' where ' . $str;
        }
        return $str;
    }

    abstract public function query($sql, $var = []);

    abstract public function multi_query($sql);

    abstract public function fetch_all($res);

    /**
     * 将结果集转化成哈希表返回,第一个字段作为键,如果总字段等于2,则用第二个字段作为值,否则用整行作为值
     */
    abstract public function fetch_hash($res);

    abstract public function fetch($res);

    abstract public function free($res);

    abstract public function parse_str($str);

    abstract public function parse_richstr($str);

    abstract public function parse_like($str);

    abstract public function last_insert_id();

    abstract public function last_error();

    abstract protected function _begin();

    abstract protected function _commit();

    abstract protected function _rollback();
}
