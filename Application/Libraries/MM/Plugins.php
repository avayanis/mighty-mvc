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
 * @package Mighty Plugins
 * @copyright  Copyright (c) 2010-2011 Andrew Vayanis. (http://www.vayanis.com)
 * @license    http://en.wikipedia.org/wiki/MIT_License     MIT License
 */

namespace MM;

class Router
{    
    /**
     * Singleton placeholder instance.
     * @var MM_Router
     */
    private static $_instance; 

    /**
     * Array of route patterns.
     * @var array
     */
    private $_routes;

    /**
     * Routed controller params.
     * @var array 
     */
    public $params;
    
    /**
     * Route current request.
     */
    public function route() {
        $controller = false;
        $action = false;

        Core::trigger('pre-route');

        foreach ($this->getRoutes() as $route => $target) {
            if (preg_match('{' . $route . '}', Core::getRequest()->getPath(), $matches)) {

                $tmp = explode('.', $target);
                $controller = $tmp[0];

                if (isset($tmp[1])) {
                    $action = $tmp[1];
                }

                array_shift($matches);
                $instance->params = (($matches) ? $matches : array());
                break;
            }
        }

        if (!$controller) {
            $controller = 'Error_Error';
            $action = 'Error';
            Core::getInstance()->getResponse()->setStatus(400);
        }

        Core::setController($controller);
        if ($action) {
            Core::setAction($action);
        }
        Core::trigger('post-route');
    }
    
    public function getRoutes()
    {
        return $this->_routes;
    }
    
	public static function init()
	{
		$instance = Core::getInstance();

		$instance->register("post-init", function() {
		    
			Router::getInstance()->route();
			
		}, (PHP_INT_MAX * -1)-1);
		
		$instance->register("pre-dispatch", function() {
		    
		}, (PHP_INT_MAX * -1)-1);
	}
	
    /**
     * Get singleton instance of MM_Router
     */
    public static function getInstance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
	
    /**
     * Private constructor to enforce singleton.
     */
    private function __construct()
    {
        $this->_routes = Core::getInstance()->load('config', 'Routes');
    }
}