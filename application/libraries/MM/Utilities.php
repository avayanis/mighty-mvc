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
       return self::getInstance()->_data[$key];
    }

    public static function set($key, $value) {
        self::getInstance()->_data[$key] = $value;
    }

    public static function getInstance() {
        if (!self::$_instance)
            self::$_instance = new self();
            
        return self::$_instance;
    }
}