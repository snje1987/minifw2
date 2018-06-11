<?php

namespace Org\Snje\Minifw;

interface TableAnalysis {

    /**
     * get info of the table(comment, engine ...)
     */
    public function get_table_status($tbname);

    /**
     * get indexs info of the table
     */
    public function get_table_index($tbname);

    /**
     * get fields info of the table
     */
    public function get_table_field($tbname);

    /**
     * get diff info to convert table status
     */
    public static function get_status_diff($tbname, $from, $to);

    /**
     * get diff info to convert table indexs
     */
    public static function get_index_diff($tbname, $from, $to, $removed);

    /**
     * get diff info to convert table fields
     */
    public static function get_field_diff($tbname, $from, $to);

    /**
     * get sql to create table
     */
    public static function create_table_sql($tbname, $tbinfo, $field, $index);

    public static function drop_table_sql($tbname);
}
