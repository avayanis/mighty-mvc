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

namespace MM;

use Closure;
use Exception;

class Core
{
	private static $_instance;
	
	private $_action = 'IndexAction';
	private $_config;
	private $_controller = 'Main\\IndexController';
	private $_dispatcher;
	private $_eventManager;
	private $_loader;
	private $_request;
	private $_response;

	public function init($path, $env)
	{
		Timer::add('start');
		
		// Define Mighty environment constants
		define('MM_ENV', $env);
		define('MM_DS', DIRECTORY_SEPARATOR);
		define('MM_APP_PATH', $path . MM_DS . 'Application');
		define('MM_LIB_PATH', MM_APP_PATH . MM_DS . 'Libraries');

		$this->setLoader(Loader::getInstance());
		
		$this->setConfig($this->load('config', 'Main'));

		if (!$debug = $this->config('debug')) {
			$this->setEventManager(new EventManager());
		} else {    
			$this->setEventManager(new Debug\EventManager());
			$this->extend('MM\\Debug\\Core');
		}

		if (($settings = $this->config('phpSettings')) !== null) {
			if (!is_array($settings)) {
				throw new Exception("'phpSettings' configuration must be of type array");
			}
			
			foreach($settings as $setting => $val) {
				ini_set($setting, $value);
			}
		}
		
		if (($plugins = $this->config('plugins')) !== null) {

			if (!is_array($plugins)) {
				throw new Exception("'plugins' configuration must be of type array");
			}
			
			foreach ($plugins as $plugin) {
				$this->extend($plugin);
			}
		}

		// Set dispatcher
		$this->setDispatcher(function($controller, $action) {
			Core::getInstance()->dispatcher($controller, $action);
		});
	}
	
	public function serve($path, $env)
	{
		try {
			$this->init($path, $env);
			
			self::trigger('post-init');
			
			self::trigger('pre-dispatch');
			
			$this->dispatch();
			
			self::trigger('post-dispatch');
			
		} catch (Exception $e) {
			print_r($e->getMessage());
			print_r($e->getFile());
			print_r($e->getLine());
		}
		
		self::trigger('pre-render');

		$this->getResponse()->render();
	}
	
	/**
	 * Framework config accessor method.
	 * @param string $key Configuration array key.
	 */
	public function config($key)
	{
		if (isset($this->_config[$key])) {
			return $this->_config[$key];
		}

		return null;
	}

	public function dispatcher($controller, $action)
	{
		$controller = $this->load('controller', $controller);
		
		$controller->$action();
	}

	/**
	 * Start output buffer and call dispatcher.
	 */
	public function dispatch()
	{
		ob_start();
		
		$controller = $this->getController();
		if (!is_string($controller)) {
			throw new Exception("No controller set.");
		}
		
		$action = $this->getAction();
		if (!is_string($action)) {
			throw new Exception("No controller set.");
		}

		$dispatcher = $this->getDispatcher();
		$dispatcher($controller, $action);
		
		$this->getResponse()->setBody(ob_get_clean());
	}
	
	/**
	 * @see MM\Loader::load()
	 * @param string $type File type.
	 * @param string $name File name.
	 */
	public function load($type, $name)
	{
		return $this->_loader->load($type, $name);
	}

	/**
	 * Initialize a Mighty compatible plugin.
	 * @param string $plugin Plugin name.
	 */
	public function extend($plugin)
	{
		$plugin::getInstance()->init();
	}

	/**
	 * Register event with framework environment. 
	 * @param string $event Event name.
	 * @param Closure $callback
	 * @param int $priority Callback priority.
	 */
	public static function register($event, $callback, $priority = 0)
	{
		Core::getInstance()->_register($event, $callback, $priority);
	}

	/**
	 * @see MM_EventManager::trigger()
	 * @param string $event Event name.
	 * @param array $input Optional array of input values to pass to registered callbacks.
	 * @param array|null $return Optional return variable to store event results.
	 */
	public static function trigger($event, array $input = array(), array &$return = null)
	{
		Core::getInstance()->_trigger($event, $input, $return);
	}

	public function setController($controller)
	{
		$this->_controller = $controller . 'Controller';
	}
	
	public function getController()
	{
		return $this->_controller;
	}
	
	public function setAction($action)
	{
		$this->_action = $action . 'Action';
	}
	
	public function getAction()
	{
		return $this->_action;
	}
	
	public function setResponse(HttpResponse $response)
	{
		$this->_response = $response;
	}
	
	public function getResponse()
	{
		return $this->_response;
	}
	
	public function setRequest(HttpRequest $request)
	{
		$this->_request = $request;
	}
	
	public function getRequest()
	{
		return $this->_request;
	}

	private function setConfig($config)
	{
		if (isset($this->_config)) {
			return;
		}
		
		$this->_config = $config;
	}
	
	public function getConfig()
	{
		return $this->_config;
	}

	/**
	 * Override default dispatch function.
	 * @param Closure $callback
	 */
	public function setDispatcher(Closure $callback)
	{
		$this->_dispatcher = $callback;
	}

	public function getDispatcher()
	{
		return $this->_dispatcher;
	}
	
	private function setEventManager(EventManager $eventManager)
	{
		$this->_eventManager = $eventManager;
	}
	
	public function getEventManager()
	{
		return $this->_eventManager;
	}
	
	private function setLoader(Loader $loader)
	{
		if (isset($this->_loader)) {
			return;
		}
		
		$this->_loader = $loader;
	}
	
	private function _register($event, $callback, $priority)
	{
		$this->_eventManager->register($event, $callback, $priority);
	}
	
	private function _trigger($event, array $input = array(), array &$return = null)
	{
		$this->_eventManager->trigger($event, $input, $return);
	}
	
	private function __construct()
	{
		$this->setRequest(new HttpRequest(array(
							'uri' => $_SERVER['REQUEST_URI'],
							'method' => $_SERVER['REQUEST_METHOD'])));
							
		$this->setResponse(new HttpResponse());
	}
	
	public static function getInstance()
	{
		if (!self::$_instance) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
}

class EventManager
{
	/**
	 * MM_EventManager configuration.
	 * @var array
	 */
	protected $_config = array(
		'max_depth' => 20,
	);

	/**
	 * Array of events
	 * @var array
	 */
	protected $_events = array();

	/**
	 * @var array
	 */
	protected $_stack = array();

	/**
	 * Constructor - create new Mighty EventManager.
	 */
	public function __construct()
	{
		$config = Core::getInstance()->config('events');

		if ($config) {
			$this->setConfig($config);
		}
	}

	/**
	 * Register an event with the event manager
	 * @param string $event
	 * @param Closure $callback
	 * @param int $priority
	 */
	public function register($events, Closure $callback, $priority = 0)
	{
		if (!is_int($priority)) {
			throw new Exception("Event priorities must be of type integer");
		}
	
		if (!is_array($events)) {
			$events = array($events);
		}

		foreach ($events as $event) {
			// Register event
			if (!isset($this->_events[$event])) {
				$this->_events[$event] = new \SplPriorityQueue();
			}
			
			$this->_events[$event]->insert($callback, $priority);
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
		if (isset($this->_config['max_depth']) && 
			(($max = $this->_config['max_depth']) && 
			($count = count($this->_stack)) > $max)) {
			throw new Exception("Maximum event depth ($max) reached.");
		}

		if (!isset($this->_events[$event])) {
			return;
		}
	 
		$this->_stack[] = $event;
		$output = '';
		foreach ($this->_events[$event] as $callback) {
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

		// Append output to framework request buffer
		Core::getInstance()->getResponse()->append($output);
			
		array_pop($this->_stack);
	}

	public function setConfig($config)
	{
		$this->_config = $config;
	}
}

class HttpRequest
{
	private $_fragment = null;
	private $_host = null;
	private $_method = null;
	private $_pass = null;
	private $_path = null;
	private $_port = null;
	private $_query = null;
	private $_scheme = null;
	private $_user = null;
	
	public function __construct(array $options)
	{
		foreach ($options as $property => $option) {
			
			switch ($property) {
				case 'uri':
					$parsed = parse_url($option);
					
					foreach ($parsed as $attribute => $value) {
						$this->{'set' . $attribute}($value);
					}
					break;
				case 'method':
					$this->_method = strtolower($option);
					break;
			}

		}
	}
	
	public function setFragment($fragment)
	{
		$this->_fragment = $fragment;
	}
	
	public function getFragment()
	{
		return $this->_fragment;
	}
	
	public function setHost($host)
	{
		$this->_host = $host;
	}
	
	public function getHost()
	{
		return $this->_host;
	}
	
	public function setMethod($method)
	{
		$this->_method = $method;
	}
	
	public function getMethod()
	{
		return $this->_method;
	}
	
	public function setPass($pass)
	{
		$this->_pass = $pass;
	}
	
	public function getPass()
	{
		return $this->_pass;
	}
	
	public function setPath($path)
	{
		$this->_path = $path;
	}
	
	public function getPath()
	{
		return $this->_path;
	}
	
	public function setPort($port)
	{
		$this->_port = $port;
	}
	
	public function getPort()
	{
		return $this->_port;
	}
	
	public function setQuery($query)
	{
		$this->_query = $query;
	}
	
	public function getQuery()
	{
		return $this->_query;
	}
	
	public function setScheme($scheme)
	{
		$this->_scheme = $scheme;
	}
	
	public function getScheme()
	{
		return $this->_scheme;
	}
	
	public function setUser($user)
	{
		$this->_user = $user;
	}
	
	public function getUser()
	{
		return $this->_user;
	}
}

class HttpResponse
{
	private $_body = '';
	private $_headers = array();
	private $_status;
	
	public function append($content)
	{
		$this->_body .= (string) $content;
	}
	
	public function prepend($content)
	{
		$this->_body = (string) $content . $this->_body;
	}
	
	public function getBody()
	{
		return $this->_body;
	}
	
	public function setBody($content)
	{
		$this->_body = (string) $content;
	}
	
	public function setStatus($status)
	{
		$this->_status = $status;
	}
	
	public function getStatus()
	{
		return $this->_status;
	}
	
	public function render()
	{
		exit($this->_body);
	}
}

class Loader
{
	private static $_instance;
	
	private $_classMap = array(
		// Debug
		'MM\\Debug\\Core' => 'MM/Debug/Debug.php',
		'MM\\Debug\\EventManager' => 'MM/Debug/Debug.php',
		'MM\\Debug\\Profiler' => 'MM/Debug/Debug.php',
		
		// Plugins
		'MM\\Router' => 'MM/Plugins.php',
		'MM\\Twig' => 'MM/Twig.php',

		// Utilities
		'MM\\Profiler' => 'MM/Utilities.php',
	);
	
	/**
	 * @var array of loaded libraries
	 */
	private $_loaded = array('MM' => true);
	
	/**
	 * Load Mighty file.
	 * @param string $type Filetype to load.
	 * @param string $name Filename to load.
	 */
	public function load($type, $name)
	{
		switch($type) {
			case 'config':
				return $this->loadConfig($name);
			case 'controller':
				return $this->loadController($name);
			case 'model':
				$this->loadModel($name);
				break;
			default:
				throw new Exception("MM\\Loader cannot load type: $type");
				break;
		}

		return $this->$call($name);
	}
	
	/**
	 * Load config file from MM_APP_PATH/Configs/
	 * @param string $name Filename.
	 */
	private function loadConfig($name)
	{
		$config = require MM_APP_PATH . MM_DS . 'Configs' . MM_DS . $name . '.php';

		if (!isset($config[MM_ENV])) {
			throw new Exception("Config not defined for environment: " . MM_ENV);
		}

		return $config[MM_ENV];
	}

	/**
	 * Load controller file from MM_APP_PATH/Controllers/ - controller name represents
	 * file path; underscores (_) are translated into directory separators.
	 * @param string $name Controller name.
	 */
	private function loadController($name)
	{
		$filePath = MM_APP_PATH . MM_DS . 'Controllers' . MM_DS . str_replace('\\', MM_DS, $name) . '.php';

		if (!file_exists($filePath)) {
			throw new Exception("Controller ($name) does not exist.");
		}
		
		require $filePath;
		
		return new $name();
	}

	/**
	 * Load config file from MM_APP_PATH/Models/
	 * @param string $model Model name.
	 */
	private function loadModel($model)
	{
		$this->_classMap["{$model}_Model"] = MM_APP_PATH . MM_DS . 'Models' . MM_DS . str_replace('_', MM_DS, $model) . '.php';
	}
	
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
	
	private function __construct()
	{   
		spl_autoload_register(array($this, 'autoload'));
	}
	
	public static function getInstance()
	{
		if (!self::$_instance) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
}

abstract class Plugin
{
	private static $_instances = array();

	protected function setup() {}

	abstract public function init();

	final public static function getInstance()
	{
		$classname = get_called_class();

		if (!isset(self::$_instances[$classname])) {
			self::$_instances[$classname] = new $classname;
		}

		return self::$_instances[$classname];
	}

	final protected function __construct()
	{
		$this->setup();	
	}

	final protected function __clone() {}
}

/**
 * 
 */
class Timer
{
	private static $_instance;

	private $_timestamps = array();

	private function __construct(){}

	public static function add($event)
	{
		$timer = self::getInstance();

		if (!is_string($event)) {
			throw new Exception("Expecting \$event to be type string.");
		}

		if (isset($timer->_timestamps[$event])) {
			throw new Exception("Event '$event' already recorded.");
		}

		$timer->_timestamps[$event] = microtime(true);
	}

	public static function get($event)
	{
		$timer = self::getInstance();

		if (!isset($timer->_timestamps[$event])) {
			throw new Exception("Event '$event' not recorded.");
		}

		return $timer->_timestamps[$event];
	}

	public static function getInstance()
	{
		if (!self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

/**
 * Mighty view - implements view design pattern.  All view scripts are 
 * searched for within MM_APP_PATH/views/.
 * @category Mighty MVC
 * @package Mighty Core
 */
class View extends \ArrayObject
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
	public function __construct($path, array $array = array())
	{
		$this->_file = MM_APP_PATH . MM_DS . 'Views' . MM_DS . $path . '.php';

		parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS);
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
	public function render($return = false)
	{
		Core::trigger('pre-viewRender', array($this->_file, $this->getArrayCopy()));
		
		ob_start();
		require $this->_file;
		$output = ob_get_clean();
		
		Core::trigger('post-viewRender', array($this->_file, $this->getArrayCopy()));
		
		if ($return){
			return $output;   
		}
		
		echo $output;
	}
}