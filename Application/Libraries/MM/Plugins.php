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

/**
 * Mighty access control list.
 * @category Mighty MVC
 * @package Mighty Plugins
 * @todo UNFINISHED! DOES NOT WORK YET!
 */
class MM_Acl
{
    /**
     * Singleton instance placeholder
     * @var MM_Acl
     */
    private static $_instance;

    /**
     * Acl configuartion.
     * @var array
     */
    private $_config;

    /**
     * Acl defined roles.
     * @var arary
     */
    private $_roles;

    /**
     * Acl defined permissions.
     * @var array
     */
    private $_permissions = array();

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct(){}

    /**
     * Plugin initialization.
     */
    public static function init() {
        $instance = self::getInstance();

        $config = MM::load('config', 'acl');
        $instance->_config = $config[MM_ENV];

        MM::register('post-init', function() {
            MM_Acl::initialize(); 
        });
    }

    /**
     * Initialize roles and permissions.
     */
    public static function initialize() {
        $instance = self::getInstance();

        foreach ($instance->_config['resources'] as $resource => $roles) {
            if (preg_match('{' . $resource . '}', MM::$request)) {
                $instance->_roles = $roles;
            } 
        }
    }


    /**
     * @param $role string Permission to test
     */
    public static function hasPermission($role) {
        if (!self::get_instance()->_roles)
            return true;
    }

    /**
     * Return singleton instance
     */
    public static function getInstance() {
        if (!self::$_instance)
            self::$_instance = new self();

        return self::$_instance;
    }
}

/**
 * Provide general layout support for applications. Layouts are implemented
 * as views.  Components are placeholders for view properties.
 * @category Mighty MVC
 * @package Mighty Plugins
 * @todo Retest MM_Layout
 */
class MM_Layout
{
    /**
     * Singleton instance placeholder
     * @var MM_Layout
     */
    private static $_instance = null;

    /**
     * Output container for layout
     * @var array
     */
    private $_components = array();
    
    /**
     * Layout file path
     * @var string
     */
    private $_layout;

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct(){}

    /**
     * Set layout file path.
     * @param string $layout File path.
     */
    public function setLayout($layout) {
        $this->_layout = $layout;
    }

    /**
     * Return layout file path.
     */
    public function getLayout() {
        return $this->_layout;
    }

    /**
     * @param string $key Component name.
     * @param string $contents Component contents.
     */
    public function setComponent($key, $contents) {
        $this->_components[$key] = $contents;
    }

    /**
     * Return requested component or all components if no key is provided.
     * @param string|null $key Component name.
     */
    public function getComponent($key = null) {
        if ($key) {
            $this->_components[$key];
        }
        
        $this->_components;
    }

    /**
     * Render layout
     */
    public function render() {
        $view = new MM_View($this->_layout);
        $view->exchangeArray($this->_components);
        echo $view->render();
    }

    /**
     * Plugin initialization
     */
    public static function init() {
        // Get configuration
        $config = MM::config('layout');
        
        if (!$config['default']) {
            throw new Exception('Default layout not set in config');
        }

        // Initialize singletong
        $instance = self::getInstance();

        // Configure layout
        $instance->setLayout($config['default']);
        
        MM::register('post-dispatch', function($layout) use ($instance) {
            $layout->render();
        }, (PHP_INT_MAX * -1)-1);
    }

    /**
     * Return singleton instance
     */
    public static function getInstance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
}

/**
 * Mighty implementation of RedBean.  Refer to http://redbeanphp.com/ for
 * RedBean documentation.
 * @category Mighty MVC
 * @package Mighty Plugins
 * @link http://redbeanphp.com/
 */
class MM_Redbean
{
    /**
     * Plugin initialization.
     */
    public static function init() {
        MM::register('post-init', function() {
            $configs = MM::config('redbean');

			if (!is_array($configs)) {
				throw new Exception("RedBean configuration missing.");
			}
	
            // Load RedBean DB ORM
            MM::load('extension', 'RB');

            foreach ($configs as $dbname => $config) {
                // Configure connection
                $dsn = "{$config['connector']}:host={$config['host']};dbname={$config['name']};port={$config['port']}";

                if (!isset($config['mode'])) {
                    $config['mode'] = 'production';
                }
                
                switch ($config['mode']) {
                    case 'development':
                        MM_Registry::set("redbean-$dbname", RedBean_Setup::kickstartDev($dsn, $config['user'], $config['pass']));
                        break;
                    case 'debug':
                        MM_Registry::set("redbean-$dbname", RedBean_Setup::kickstartDebug($dsn, $config['user'], $config['pass']));
                        break;
                    case 'production-frozen':
                        MM_Registry::set("redbean-$dbname", RedBean_Setup::kickstartFrozen($dsn, $config['user'], $config['pass']));                       
                        break;
                    case 'production':
                    default:
                        MM_Registry::set("redbean-$dbname", RedBean_Setup::kickstart($dsn, $config['user'], $config['pass']));
                        break;
                }
            }
        });
    }
}

/**
 * Mighty Router provides rule based routing.  Routes should be defined in
 * Confings/Routes.php.
 * @category Mighty MVC
 * @package Mighty Plugins
 */
class MM_Router
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
     * Private constructor to enforce singleton.
     */
    private function __construct(){}

    /**
     * Plugin initialization.
     */
    public static function init() {
        // Initialize singleton instance
        $instance = self::getInstance();

        // Load routes        
        $routes = MM::load('config', 'Routes');
        $instance->_routes = @$routes[MM_ENV];

        // Register router events
        MM::register('post-init', function() {
            MM_Router::route();
        }, (PHP_INT_MAX * -1)-1);

        MM::register('pre-dispatch', function() {
            MM::$dispatch = function($name, $action) {
                $instance = MM_Router::getInstance();
                
                // Load controller
                $controller = MM::load('controller', $name);

                call_user_func_array(array($controller, $action), 
                    $instance->params);
            };
        }, (PHP_INT_MAX * -1)-1);
    }

    /**
     * Route current request.
     */
    public static function route() {
        $instance   = self::getInstance();
        $controller = false;
        $action = false;

        MM::trigger('pre-route');

        foreach ($instance->_routes as $route => $target) {
            if (preg_match('{' . $route . '}', MM::$request, $matches)) {

                $tmp = explode('.', $target);
                $controller = $tmp[0];
                @$action = $tmp[1];

                array_shift($matches);
                break;
            }
        }

        if (!$controller) {
            $controller = 'Error_Error';
            $action = 'Error';
            MM::$status = 404;
        }

        $instance->params = (($matches) ? $matches : array());

        MM::setController($controller);
        if ($action) {
            MM::setAction($action);
        }
        MM::trigger('post-route');
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
}