<?php

class MM_Utilities {

    public static function redirect($path) {
        $tmp = explode('index.php', $_SERVER['DOCUMENT_URI']);
        $path = ($path[0] == '/') ? ltrim($path, '/') : $path;

        header ('Location: ' . $tmp[0] . $path);
    }
}

class MM_Registry {
    private static $_instance;

    private $_data;

    private function __construct() {}

    public static function get($key) {
       return self::get_instance()->_data[$key];
    }

    public static function set($key, $value) {
        self::get_instance()->_data[$key] = $value;
    }

    public static function get_instance() {
        if (!self::$_instance)
            self::$_instance = new self();
            
        return self::$_instance;
    }
}