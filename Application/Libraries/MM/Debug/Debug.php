<?php

class MM_Debug
{
	private static $_instance = null;
	
	private $_eventStack;
	
	public static function init() {
		
		MM::register('post-dispatch', function() {
			
			$response = MM::$response;
			
			// Find end of head
			$pos = strpos($response, '</head>');
			$head = substr($response, 0, $pos);
			$rest = substr($response, $pos);
			
			MM::$response = $head . '<script type="text/javascript" src="/mm_debug/js"></script>' . $rest;
			
		}, PHP_INT_MAX);
		
		MM::register('post-init', function() {
			
			$requestPath = explode('/', MM::$request);
			if ($requestPath[1] == 'mm_debug') {
				switch($requestPath[2]) {
					case 'js':
						header('Content-Type: application/javascript');
						exit(require('MMDebug.js'));
						break;
					case 'css':
						header('Content-Type: text/css');
						exit(require('MMDebug.css'));
						break;
					default:
						break;
				}
			}
			
		}, PHP_INT_MAX);
		
	}
	
	private function __construct() {}

	public static function getInstance()
	{
		if (!self::$_instance) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
}

class MM_DebugEventManager extends MM_EventManager
{
	private $_debugger;
	
	public function __construct()
	{
		parent::__construct();
		$this->_debugger = MM_Debug::getInstance();
	}
	
	public function trigger($event, array $input = array(), array &$return = null)
	{
		parent::trigger($event, $input, $return);
	}
}

