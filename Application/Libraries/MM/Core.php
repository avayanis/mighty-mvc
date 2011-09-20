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
 * @package Mighty Core
 * @copyright  Copyright (c) 2010-2011 Andrew Vayanis. (http://www.vayanis.com)
 * @license    http://en.wikipedia.org/wiki/MIT_License     MIT License
 */

/**
 * Mighty MVC framework.
 * @category Mighty MVC
 * @package Mighty Core
 */
class MM
{
    /**
     * @var string
     */
    private static $_action = 'IndexAction';

    /**
     * Runtime configuration.
     * @var array
     */
    private static $_config;

    /**
     * @var string
     */
    private static $_controller = 'Main_IndexController';

    /**
     * @var Exception
     */
    private static $_error = null;

    /**
     * Mighty event manager instance.
     * @var MM_EventManager
     */
    private static $_eventManager;

    /**
     * Mighty class loader instance.
     * @var MM_Loader
     */
    private static $_loader;

    /**
     * Dispatch callback function - called after pre-dispatch event.
     * @var Closure
     */
    public static $dispatch;

    /**
     * Array of headers to output before response body.
     * @var array
     */
    public static $headers = array();

    /**
     * Response body.
     * @var string
     */
    public static $response;

    /**
     * REQUEST_URI path
     * @var string
     */
    public static $request;

    /**
     * Status code.
     * @var int
     */
    public static $status = 200;

    /**
     * Request system time.
     * @var float
     */
    public static $timeStart;

    /**
     * Initialize the framework environment.
     * @param string $path Path to application parent directory.
     * @param string $env Configuration section to load.
     */
    private static function init($path, $env)
    {
        self::$timeStart = microtime(true);

        // Define Mighty environment constants
        define('MM_DS', DIRECTORY_SEPARATOR);
        define('MM_ENV', $env);
        define('MM_APP_PATH', $path . MM_DS . 'Application');
        define('MM_LIB_PATH', MM_APP_PATH . MM_DS . 'Libraries');

        // Setup request variable
        self::$request = ($pos = strpos($_SERVER['REQUEST_URI'], '?')) ? 
                            substr($_SERVER['REQUEST_URI'], 0, $pos) : $_SERVER['REQUEST_URI'];

        // Initialize loader
        self::$_loader = MM_Loader::getInstance();
        
        // Setup autoloader
        spl_autoload_register(array(self::$_loader, 'autoload'));

        // Load main config
        self::$_config = self::load('config', 'Main');

        // Load event manager
        if (!isset(self::$_config['debug']) || !self::$_config['debug']) {
            self::$_eventManager = new MM_EventManager();
        } else {
            self::$_eventManager = new MM_DebugEventManager();
            MM::extend('MM_Debug');
        }

        // Modify PHP environment configuration
        if (isset(self::$_config['php_settings'])) {            
            foreach (self::$_config['php_settings'] as $setting => $value) {
                ini_set($setting, $value);
            }
        }

        // Load plugins
        if (isset(self::$_config['plugins'])) {
            foreach (self::$_config['plugins'] as $plugin) {
                MM::extend($plugin);
            }
        }

        // Set dispatcher
        MM::setDispatcher(function($controller, $action) {
            MM::dispatcher($controller, $action);
        });
    }

    /**
     * Serve an HTTP request through the framework.
     * @param string $path Path to application parent directory.
     * @param string $env Configuration section to load.
     */
    public static function serve($path, $env)
    {
        try {
            // initialize framework
            self::init($path, $env);

            // post init event
            self::trigger('post-init');

            // pre dispatch event
            self::trigger('pre-dispatch');

            MM::dispatch();

            // post dispatch event
            self::trigger('post-dispatch');
        } catch (Exception $e) {
            MM::$_error = $e;

            if (MM::$status == 200) {
                MM::$status = 500;
            }

            MM::setController('Error_Error');
            MM::setAction('Error');

            // pre error event
            self::trigger('pre-error');

            MM::$response .= ob_get_clean();
            
            MM::setDispatcher(function($controller, $action) {
                MM::dispatcher($controller, $action);
            });

            MM::$headers['Status'] = MM::$status;
            MM::dispatch();

            // post error event
            self::trigger('post-error');
        }

        // render response to client
        self::render();
    }
    
    /**
     * Load controller and call action.
     * @param string $controller Controller name.
     * @param string $action Action name.
     */
    public static function dispatcher($controller, $action)
    {
        // Load controller
        $controller = MM::load('controller', $controller);

        // Dispatch request
        $controller->$action();
    }
    
    /**
     * Start output buffer and call dispatcher.
     */
    public static function dispatch()
    {
        ob_start();
        
        $dispatcher = self::$dispatch;
        $dispatcher(self::$_controller, self::$_action);
        
        MM::$response .= ob_get_clean();
    }

    /**
     * Return any errors caught by Mighty.
     */
    public static function getError()
    {
        return self::$_error;
    }

    /**
     * Framework config accessor method.
     * @param string $key Configuration array key.
     */
    public static function config($key)
    {
        if (isset(self::$_config[$key])) {
            return self::$_config[$key];
        }
    }

    /**
     * @see MM_Loader::load()
     * @param string $type File type.
     * @param string $name File name.
     */
    public static function load($type, $name)
    {
        return self::$_loader->load($type, $name);
    }

    /**
     * Initialize a Mighty compatible plugin.
     * @param string $plugin Plugin name.
     */
    public static function extend($plugin)
    {
        $plugin::init();
    }

    /**
     * Register event with framework environment. 
     * @param string $event Event name.
     * @param Closure $callback
     * @param int $priority Callback priority.
     */
    public static function register($event, $callback, $priority = 0)
    {
        self::$_eventManager->register($event, $callback, $priority);
    }

    /**
     * Output headers and send response to client.  If no $response is provided,
     * output MM::$response.
     * @param string|null $response Optional content to output to client.
     */
    public static function render($response = null)
    {
        foreach (self::$headers as $header => $value) {
            header("$header: $value");
        }

        echo ($response) ? $response : self::$response;
    }

    /**
     * Override dispatch action.
     * @param string $action Method name.
     */
    public static function setAction($action)
    {
        self::$_action = "{$action}Action";
    }

    /**
     * Override default dispatch function.
     * @param Closure $callback
     */
    public static function setDispatcher(Closure $callback)
    {
        self::$dispatch = $callback;
    }

    /**
     * Override dispatch controller.
     * @param string $controller Controller name.
     */
    public static function setController($controller)
    {
        self::$_controller = "{$controller}Controller";
    }

    /**
     * @see MM_EventManager::trigger()
     * @param string $event Event name.
     * @param array $input Optional array of input values to pass to registered callbacks.
     * @param array|null $return Optional return variable to store event results.
     */
    public static function trigger($event, array $input = array(), array &$return = null)
    {
        self::$_eventManager->trigger($event, $input, $return);
    }
}

/**
 * Framework event manger - enables plugin support.
 * @category Mighty MVC
 * @package Mighty Core
 */
class MM_EventManager
{
    /**
     * MM_EventManager configuration.
     * @var array
     */
    private $_config;

    /**
     * Array of events
     * @var array
     */
    private $_events = array();

    /**
     * @var array
     */
    private $_stack = array();

    /**
     * @var array
     */
    private $_status = array();

    /**
     * Constructor - create new Mighty EventManager.
     */
    public function __construct()
    {
        $this->_config = MM::config('events');
    }

    /**
     * Register an event with the event manager
     * @param string $event
     * @param Closure $callback
     * @param int $priority
     */
    public function register($events, Closure $callback, $priority = 0)
    {
        if (!is_array($events)) {
            $events = array($events);
        }

        foreach ($events as $event) {
            // Set sorting status for event
            $this->_status[$event] = false;

            // Register event
            $this->_events[$event][$priority][] = $callback;
        }
    }

    /**
     * Trigger all callbacks associated with an event
     * @param string $event Event name.
     * @param array $input Optional array of input values to pass to registered callbacks.
     * @param array|null $return Optional return variable to store event results.
     */
    public function trigger($event, array $input = array(), array &$return = null)
    {
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

        $output = '';
        foreach ($this->_events[$event] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if ($callback instanceof Closure) {
                    // Reset result
                    $result = '';

                    // Create buffer for callback
                    ob_start();

                    // Execute callback
                    $result = $callback($input);
                    if ($result) {
                        $return[$event] = $result;
                    }
                    
                    // End buffer and store output
                    $output .= ob_get_clean();
                }
            }
        }

        // Append output to framework request buffer
        MM::$response .=  $output;
            
        array_pop($this->_stack);
    }
}

/**
 * Mighty class loader.
 * @category Mighty MVC
 * @package Mighty Core
 */
class MM_Loader
{
    /**
     * @var array Map of classes to relative library locations.
     */
    private $_classMap = array(
        'MM_Acl' => 'MM/Plugins.php',
        'MM_Debug' => 'MM/Debug/Debug.php',
        'MM_DebugEventManager' => 'MM/Debug/Debug.php',
        'MM_Layout' => 'MM/Plugins.php',
        'MM_Redbean' => 'MM/Plugins.php',
        'MM_Registry' => 'MM/Utilities.php',
        'MM_Router' => 'MM/Plugins.php',
        'MM_Session' => 'MM/Plugins.php',
        'MM_Utilities' => 'MM/Utilities.php',
    );

    /**
     * @var array of loaded libraries
     */
    private $_loaded = array('MM' => true);
    
    /**
     * @var MM_Loader Singleton instance
     */
    private static $_instance;

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct(){}

    /**
     * Get singleton instance of MM_Loader
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * Mighty autoloader.
     * @param string $class Classname
     */
    public function autoload($class)
    {
        if (!isset($this->_classMap[$class])) {
            $class_path = explode('_', $class);
            $library    = $class_path[0];
            $core       = MM_LIB_PATH . MM_DS. $library . MM_DS . 'Core.php';

            if (file_exists($core) && !isset($this->_loaded[$library])) {
                require $core;
                $this->_loaded[$library] = true;

                $this->_classMap = array_merge($library::_libraries(), $this->_classMap);

                if (!isset($class_path[1]))
                    return;

                if (!isset($this->_classMap[$class])) {
                    /** 
                     * Class not defined in loader.  We assume it is included in 
                     * core and return from autoloader.  If the class has not been loaded
                     * then an exception will be thrown.
                     */

                    return; 
                }
            }
        }

        if ($location = $this->_classMap[$class]) {
            if ($location[0] == '/') {
                require $location;
            } else {
                require MM_LIB_PATH . MM_DS . $location;
            }
        }
    }
    
    /**
     * Load Mighty file.
     * @param string $type Filetype to load.
     * @param string $name Filename to load.
     */
    public function load($type, $name)
    {
        $call = 'load' . ucfirst($type);
        return $this->$call($name);
    }

    /**
     * Load config file from MM_APP_PATH/Configs/
     * @param string $name Filename.
     */
    private function loadConfig($name)
    {
        $config = require MM_APP_PATH . MM_DS . 'Configs' . MM_DS . $name . '.php';
        return $config[MM_ENV];
    }

    /**
     * Load controller file from MM_APP_PATH/Controllers/ - controller name represents
     * file path; underscores (_) are translated into directory separators.
     * @param string $name Controller name.
     */
    private function loadController($name)
    {
        $filePath = MM_APP_PATH . MM_DS . 'Controllers' . MM_DS . str_replace('_', MM_DS, $name) . '.php';
        
        if (!file_exists($filePath)) {
            throw new Exception("Controller ($name) does not exist.");
        }
        
        require MM_APP_PATH . MM_DS . 'Controllers' . MM_DS . str_replace('_', MM_DS, $name) . '.php';
        
        return new $name();
    }

    /**
     * Load extension file from MM_APP_PATH/Extensions/
     * @param string $path Relative file path in extensions directory.
     */
    private function loadExtension($path)
    {
        require MM_APP_PATH . MM_DS . 'Extensions' . MM_DS . $path . '.php';
    }

    /**
     * Load config file from MM_APP_PATH/Models/
     * @param string $model Model name.
     */
    private function loadModel($model)
    {
        $this->_classMap["{$model}_Model"] = MM_APP_PATH . MM_DS . 'Models' . MM_DS . str_replace('_', MM_DS, $model) . '.php';
    }
}

/**
 * Mighty view - implements view design pattern.  All view scripts are 
 * searched for within MM_APP_PATH/views/.
 * @category Mighty MVC
 * @package Mighty Core
 */
class MM_View extends ArrayObject
{
    /**
     * @var string Absolute file path to view script.
     */
    protected $_file;

    /**
     * Constructor - create new Mighty view.
     * @param string $path Relative file path to view script..
     * @param array|null $array Array to initiate ArrayObject storage.
     */
    public function __construct($path, array $array = null)
    {
        $this->_file = MM_APP_PATH . MM_DS . 'Views' . MM_DS . $path . '.php';

        parent::__construct(array(), ArrayObject::ARRAY_AS_PROPS);
        
        if ($array) {
            $this->exchangeArray($array);
        }
    }
    
    /**
     * Override ArrayObject's default get method so that errors are not thrown
     * when requesting undefined properties.
     * @param string $index View property to return.
     */
    public function offsetGet($index)
    {
        if (!parent::offsetExists($index)) {
            return;
        }
        
        return parent::offsetGet($index);
    }
    
    /**
     * Create a new View object with current attributes and render.
     * @param string $path Relative file path to view script.
     */
    public function display($path)
    {
        $view = new MM_View($path, $this->getArrayCopy());
        return $view->render();
    }

    /**
     * Render view script.
     */
    public function render()
    {
        MM::trigger('pre-viewRender', array($this->_file, $this->getArrayCopy()));
        
        ob_start();
        require $this->_file;
        $output = ob_get_clean();
        
        MM::trigger('post-render', array($this->_file, $this->getArrayCopy()));
        return $output;
    }
}