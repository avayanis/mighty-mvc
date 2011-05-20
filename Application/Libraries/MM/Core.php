<?php
/**
 * mightyMVC
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
 * @package MM
 */

/**
 * mightyMVC framework
 * 
 * $package MM
 */
class MM
{
    private static $_action = 'IndexAction';
    private static $_config;
    private static $_controller = 'Main_IndexController';
    private static $_error = null;
    private static $_eventManager;
    private static $_loader;

    public static $dispatch;
    public static $headers = array();
    public static $output;
    public static $request;
    public static $status = 200;
    public static $timeStart;

    /**
     * Initialize the framework environment
     *
     * @access private
     * @static
     */
    private static function init($path, $env) {
        self::$timeStart = microtime(true);

        // Define mightyMVC environment constants
        define('MM_DS', DIRECTORY_SEPARATOR);
        define('MM_ENV', $env);
        define('MM_APP_PATH', $path . MM_DS . 'Application');
        define('MM_LIB_PATH', MM_APP_PATH . MM_DS . 'Libraries');

        // Setup request variable
        self::$request = parse_url($_SERVER['REQUEST_URI']);

        // Initialize loader
        self::$_loader = new MM_Loader();
        
        // Setup autoloader
        spl_autoload_register(array(self::$_loader, 'autoload'));

        // Load main config
        self::$_config = self::load('config', 'Main');

        // Load event manager 
        self::$_eventManager = new MM_EventManager();

        // Modify PHP environment configuration
        if (@$settings = self::$_config[MM_ENV]['php_settings']) {
            foreach ($settings as $setting => $value) {
                ini_set($setting, $value);
            }
        }

        // Load plugins
        if (@$plugins = self::$_config[MM_ENV]['plugins']) {
            foreach ($plugins as $plugin) {
                MM::extend($plugin);
            }
        }

        // Set dispatcher
        MM::setDispatcher(function($controller, $action) {
            MM::dispatcher($controller, $action);
        });
    }

    /**
     * Serve an HTTP request through the framework
     *
     * @param string $path
     * @param string $env
     * @static
     */
    public static function serve($path, $env) {
        try {
            // Initialize framework
            self::init($path, $env);

            // Fire post init event
            self::trigger('post-init');

            // Fire pre dispatch event
            self::trigger('pre-dispatch');

            MM::dispatch();

            // Fire post dispatch event
            self::trigger('post-dispatch');
        } catch (Exception $e) {
            MM::setController('Error_Error');
            MM::setAction('Error');

            MM::$_error = $e;

            // Fire pre error event
            self::trigger('pre-error');

            MM::$output .= ob_get_clean();

            MM::setDispatcher(function($controller, $action) {
                MM::dispatcher($controller, $action);
            });

            MM::dispatch();

            // Fire post error event
            self::trigger('post-error');
        }

        self::render();

        // Cleanup
        self::trigger('cleanup');
    }
    
    public static function dispatcher($controller, $action) {
        // Load controller
        $controller = MM::load('controller', $controller);

        // Dispatch request
        $controller->$action();
    }
    
    public static function dispatch() {
        ob_start();
        
        $dispatcher = self::$dispatch;
        $dispatcher(self::$_controller, self::$_action);
        
        MM::$output .= ob_get_clean();
    }

    public static function getError() {
        return self::$_error;
    }

    /*
     * Framework config accessor method
     *
     * @param string $key 
     * @static
     */
    public static function config($key) {
        return @self::$_config[MM_ENV][$key];
    }

    /*
     *
     */
    public static function load($type, $name) {
        $call = "load" . ucfirst($type);
        return self::$_loader->$call($name);
    }

    /*
     *
     */
    public static function extend($plugin) {
        $plugin::init();
    }

    /**
     * Register event with framework environment. 
     *
     * @param string $event
     * @param Closure $callback
     * @param int $priority
     */
    public static function register($event, $callback, $priority = 0) {
        self::$_eventManager->register($event, $callback, $priority);
    }

    /*
     *
     */
    public static function render($output = null) {
        foreach (self::$headers as $header => $value) {
            header("$header: $value");
        }

        echo ($output) ? $output : self::$output;
    }

    /*
     *
     */
    public static function setAction($action) {
        self::$_action = "{$action}Action";
    }

    /*
     *
     */
    public static function setDispatcher(Closure $callback) {
        self::$dispatch = $callback;
    }

    /*
     *
     */
    public static function setController($controller) {
        self::$_controller = "{$controller}Controller";
    }

    /**
     * Trigger framework event
     *
     * @param string $event
     */
    public static function trigger($event, array $input = array(), &$return = null) {
        self::$_eventManager->trigger($event, $input, $return);
    }
}

/**
 * Framework event manger - enables plugin support.
 *
 * @package MM
 */
class MM_EventManager
{
    private $_config;
    private $_events = array();
    private $_stack = array();
    private $_status = array();

    /*
     *
     */
    public function __construct() {
        $this->_config = MM::config('events');
    }

    /**
     * Register an event with the event manager
     *
     * @param string $event
     * @param Closure $callback
     * @param int $priority
     */
    public function register($event, Closure $callback, $priority = 0) {
        // Set sorting status for event
        $this->_status[$event] = false;
        
        // Register event
        $this->_events[$event][$priority][] = $callback;
    }

    /**
     * Trigger all callbacks associated with an event
     *
     * @param string $event
     */
    public function trigger($event, array $input = array(), &$return = null) {
        if ((($max = $this->_config['max_depth']) && ($count = count($this->_stack)) > $max)) {
            throw new Exception("Maximum event depth ($max) reached.");
        }

        if (!isset($this->_events[$event])) {
            return;
        }

        if (!$this->_status[$event]) {
            krsort($this->_events[$event]);
            $this->_status[$event] = true;
        }
     
        $this->_stack[] = $event;

        $output = "";
        foreach ($this->_events[$event] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if ($callback instanceof Closure) {
                    // Create buffer for callback
                    ob_start();

                    // Execute callback
                    $return = $callback($input);
                    
                    // End buffer and store output
                    $output .= ob_get_clean();
                }
            }
        }

        # Append output to framework request buffer
        MM::$output .=  (@!$this->_config['debug']) ? $output : 
                        "<p>######## Start: $event ########</p>" .$output . "<p>######## End: $event ########</p>";
            
        array_pop($this->_stack);
    }
}

/**
 * mightyMVC class loader
 *
 * @package MM
 */
class MM_Loader
{
    private $_classMap = array(
        'MM_Acl' => 'MM/Plugins.php',
        'MM_Layout' => 'MM/Plugins.php',
        'MM_Redbean' => 'MM/Plugins.php',
        'MM_Router' => 'MM/Plugins.php',
        'MM_Session' => 'MM/Plugins.php',
        'MM_Registry' => 'MM/Utilities.php',
        'MM_Utilities' => 'MM/Utilities.php',
    );

    /*
     *
     */
    public function autoload($class) {
        if (@!$location = $this->_classMap[$class]) {
            $class_path = explode('_', $class);
            $library    = $class_path[0];
            $core       = MM_LIB_PATH . MM_DS. strtolower($library) . MM_DS . 'core.php';
            
            if (file_exists($core)) {
                require $core;

                $this->_classMap = array_merge($library::_libraries(), $this->_classMap);

                if (!isset($class_path[1]))
                    return;

                if (@!$location = $this->_class_map[$class]) {
                    # Class not defined in loader.  We assume it is included in 
                    # core and return from autoloader.  If the class has not been loaded
                    # then an exception will be thrown.

                    return; 
                }
            }
        }

        if ($location) {
            if ($location[0] == '/') {
                require $location;
            } else {
                require MM_LIB_PATH . MM_DS . $location;
            }
        }
    }

    /*
     *
     */
    public function loadConfig($name) {
        return require MM_APP_PATH . MM_DS . 'configs' . MM_DS . $name . '.php';
    }

    /*
     *
     */
    public function loadController($controller) {
        require MM_APP_PATH . MM_DS . 'controllers' . MM_DS . str_replace('_', MM_DS, $controller) . '.php';
        
        return new $controller();
    }
    
    /*
     *
     */
    public function loadExtension($path) {
        require MM_APP_PATH . MM_DS . 'extensions' . MM_DS . $path . '.php';
    }
    
    public function loadModel($model) {
        $this->_classMap[ucfirst($model) . '_Model'] = MM_APP_PATH . MM_DS . 'models' . MM_DS . $model . '.php';
    }
}

/*
 *
 */
class MM_View extends ArrayObject
{
    protected $_file;
    protected $_vars;

    /*
     *
     */
    public function __construct($path, $vars = array()) {
        $this->_file = MM_APP_PATH . MM_DS . 'views' . MM_DS . $path . '.php';
        $this->_vars = $vars;
    }
    
    public function getVars() {
        return $this->_vars;
    }
    
    public function setVars($vars) {
        $this->_vars = $vars;
        return $this;
    }
    
    public function display($path) {
        $view = new MM_View($path, $this->_vars);
        return $view->render();
    }

    /*
     *
     */
    public function render() {
        MM::trigger('pre-render', array($this->_file, $this->_vars));
        
        ob_start();
        require $this->_file;
        $output = ob_get_clean();
        
        MM::trigger('post-render', array($this->_file, $this->_vars));
        return $output;
    }
}