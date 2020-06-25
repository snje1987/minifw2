<?php

namespace Org\Snje\Minifw;

use Org\Snje\Minifw as FW;
use Org\Snje\Minifw\Exception;

class Pager {

    public $cur_page; //当前页码
    public $max_page; //最大页码
    public $page_list; //当前显示的页码列表
    public $item_count; //信息总数
    public $item_per_page; //每页显示的信息数
    public $start; //第一条显示的信息的位置
    public $prefix; //页码的前缀
    public $tail; //页码的后缀
    public $page_list_len;

    public function __construct($cur_page, $item_per_page, $prefix = '', $tail = '', $page_list_len = 10) {
        $this->cur_page = intval($cur_page);
        $this->item_per_page = $item_per_page;
        $this->prefix = $prefix;
        $this->tail = $tail;
        $this->page_list_len = $page_list_len;
    }

    public function cal() {
        if ($this->item_count > 0) {
            $this->max_page = intval(($this->item_count - 1) / $this->item_per_page) + 1;
        }
        else {
            $this->max_page = 1;
        }
        if ($this->cur_page < 1) {
            $this->cur_page = 1;
        }
        elseif ($this->cur_page > $this->max_page) {
            $this->cur_page = $this->max_page;
        }

        $this->start = ($this->cur_page - 1) * $this->item_per_page;
        if ($this->max_page <= $this->page_list_len) {
            $this->page_list = range(1, $this->max_page);
        }
        else {
            $center = $this->cur_page;
            $list_center = ceil($this->page_list_len / 2);
            if ($center < $list_center) {
                $center = $list_center;
            }
            elseif ($center > $this->max_page - floor($this->page_list_len / 2)) {
                $center = $this->max_page - floor($this->page_list_len / 2);
            }
            $start = $center - $list_center + 1;
            $end = $start + $this->page_list_len - 1;
            $this->page_list = range($start, $end);
        }
    }

    public function get_page($instance, $condition = [], $field = [], $order = '', $group = '') {
        $this->item_count = $instance->count($condition);
        $this->cal();
        if (is_array($condition)) {

            if ($group != '') {
                $condition['_group'] = $group;
            }

            if ($order != '') {
                $condition['order'] = $order;
            }

            $condition['limit'] = $this->start . ',' . $this->item_per_page;
            return $instance->gets_by_condition($condition, $field);
        }
        else {
            if ($group != '') {
                $condition .= ' group by ' . $group;
            }

            if ($order != '') {
                $condition .= ' order by ' . $order;
            }

            $condition .= ' limit ' . $this->start . ',' . $this->item_per_page;
            $sql = 'select ' . $field . ' from ' . $condition;

            return $instance->gets_by_query($sql);
        }
    }

    public function get_page_direct($db, $condition, $field, $order = '', $var = [], $group = false) {
        $sql = '';
        if ($group) {
            $sql = 'select count(*) as "count" from ((select count(*) from ' . $condition . ') `a`)';
        }
        else {
            $sql = 'select count(*) as "count" from ' . $condition;
        }
        $data = $db->get_query($sql, $var);
        $this->item_count = $data[0]['count'];
        $this->cal();
        if ($order != '') {
            $condition .= ' order by ' . $order;
        }
        $condition .= ' limit ' . $this->start . ',' . $this->item_per_page;
        return $db->get_query('select ' . $field . ' from ' . $condition, $var);
    }

}
