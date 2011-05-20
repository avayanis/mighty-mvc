<?php

class MM_Acl
{
    private static $_instance;

    private $_config;
    private $_roles;
    private $_permissions = array();

    public static function init() {
        $instance = self::getInstance();

        $config = MM::load('config', 'acl');
        $instance->_config = $config[MM_ENV];

        MM::register('post-init', function() {
            MM_Acl::initialize(); 
        });
    }

    public static function initialize() {
        $instance = self::getInstance();

        foreach ($instance->_config['resources'] as $resource => $roles) {
            if (preg_match('{' . $resource . '}', MM::$request['path'])) {
                $instance->_roles = $roles;
            } 
        }
    }

    public static function hasPermission($role, $test = null) {
        if (!self::get_instance()->_roles)
            return true;
    }

    public static function getInstance() {
        if (!self::$_instance)
            self::$_instance = new self();

        return self::$_instance;
    }
}

class MM_Layout {

    private static $_components;
    private static $_layout;

    public static function init() {
        // Get configuration
        $config = MM::config('layout');
        
        if (!$config['default']) {
            throw new Exception('Default layout not set in config');
        }

        // Configure layout
        self::set_layout($config['default']);
        
        MM::register('post-dispatch', function() {
            MM_Layout::render();
        }, (PHP_INT_MAX * -1)-1);
    }
    
    public static function setLayout($layout) {
        self::$_layout = $layout;
    }
    
    public static function getLayout() {
        return self::$_layout;
    }
    
    public static function setComponent($key, $contents) {
        self::$_components[$key] = $contents;
    }
    
    public static function getComponent($key = null) {
        if ($key) {
            return self::$_components[$key];
        }
        
        return self::$_components;
    }
    
    public static function render() {
        $view = new MM_View(self::$_layout);
        $view->setVars(self::$_components);
        echo $view->render();
    }
}

class MM_Redbean {

    public static function init() {
        MM::register('post-init', function() {
            $configs = MM::config('redbean');

            // Load RedBean DB ORM
            MM::load('extension', 'RB');

            foreach ($configs as $dbname => $config) {
                // Configure MySQL connection
                $dsn = "mysql:host={$config['host']};dbname={$config['name']};port={$config['port']}";

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

class MM_Router {

    private static $_instance; 

    public $params;
    public $request;
    
    private $_routes;
    
    private function __construct(){}

    public static function init() {
        # Initialize singleton instance
        $instance = self::getInstance();

        # Load routes        
        $routes = MM::load('config', 'routes');
        $instance->_routes = $routes[MM_ENV];

        # Register router events
        MM::register('post-init', function() {
            MM_Router::route();
        }, (PHP_INT_MAX * -1)-1);

        MM::register('pre-dispatch', function() {
            MM::$dispatch = function($name, $action) {
                $instance = MM_Router::getInstance();
                
                # Load controller
                $controller = MM::load('controller', $name);

                call_user_func_array(array($controller, $action), 
                    $instance->params);
            };
        }, (PHP_INT_MAX * -1)-1);
    }

    public static function route() {
        $instance   = self::getInstance();
        $controller = false;
        $action = false;

        MM::trigger('pre-route');

        foreach ($instance->_routes as $route => $target) {
            if (preg_match('{' . $route . '}', MM::$request['path'], $matches)) {

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

    public static function getInstance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}

class MM_Session {

    private static $_config;

    public static function init() {
        self::$_config = MM::config('sessions');

        if (self::$_config['auto']) {
            session_start(); 
        } else {

        }
    }

    public static function get($value) {
        if (isset($_SESSION[$value])) {
            return $_SESSION[$value];
        }
        return false;
    }
}