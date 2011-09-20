<?php
/**
 * Mighty MVC
 *
 * Copyright (C) 2010 by Andrew Vayanis
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * @category Mighty MVC
 * @package Mighty Utilities
 * @copyright  Copyright (c) 2010-2011 Andrew Vayanis. (http://www.vayanis.com)
 * @license    http://en.wikipedia.org/wiki/MIT_License     MIT License
 */

/**
 * Perform 301 redirect.
 * @category Mighty MVC
 * @package Mighty Utilities
 */
class MM_Utilities
{
    public static function isAjax()
    {
        static $isAjax = null;

        if (is_null($isAjax)) {
            $isAjax = false;
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                $isAjax = true;
            }
        }
        
        return $isAjax;
    }
    
    /**
     * @param string $path Redirect location.
     */
    public static function redirect($path) {
        $tmp = explode('index.php', $_SERVER['DOCUMENT_URI']);
        $path = ($path[0] == '/') ? ltrim($path, '/') : $path;

        header ('Location: ' . $tmp[0] . $path);
    }
}

/**
 * Mighty Registry implements registry design pattern.
 * @category Mighty MVC
 * @package Mighty Utilities
 */
class MM_Registry
{
    /**
     * Singleton instance placeholder.
     * @var MM_Registry
     */
    private static $_instance = null;

    /**
     * @var array
     */
    private $_data;

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct(){}

    /**
     * @param string $key Registry storage key.
     */
    public static function get($key) {
       return self::getInstance()->_data[$key];
    }

    /**
     * @param string $key Registry storage key.
     * @param mixed $value Registry storage value.
     */
    public static function set($key, $value) {
        self::getInstance()->_data[$key] = $value;
    }

    /**
     * Return singleton instance.
     */
    public static function getInstance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
            
        return self::$_instance;
    }
}