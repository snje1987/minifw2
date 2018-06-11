<?php

namespace Org\Snje\Minifw;

class Config {

    /**
     * @var static the instance
     */
    protected static $_instance = null;

    /**
     *
     * @param array $args
     * @return Config
     */
    public static function get($args = []) {
        if (self::$_instance === null) {
            self::$_instance = new static($args);
        }
        return self::$_instance;
    }

    public static function get_new($args = []) {
        if (self::$_instance !== null) {
            self::$_instance = null;
        }
        return self::get($args);
    }

    /**
     * @var array config data
     */
    protected $data;
    protected $config_path;

    public function __construct($config_path) {
        $this->config_path = $config_path;
        $this->load_config();
    }

    /**
     * Get Config item
     *
     * @param string $section Config section
     * @param string $key Config key
     * @param mixed $default Default value when not exists
     * @return mixed Config value
     */
    public function get_config($section, $key = '', $default = null) {
        if ($section === '' || !isset($this->data[$section])) {
            return null;
        }
        if ($key === '') {
            return $this->data[$section];
        }
        if (!isset($this->data[$section][$key])) {
            return $default;
        }
        return $this->data[$section][$key];
    }

    /**
     * Load config data
     */
    public function load_config() {
        $cfg = [];
        require __DIR__ . '/defaults.php';
        if (file_exists($this->config_path)) {
            require $this->config_path;
        }
        $this->data = $cfg;
    }

}
