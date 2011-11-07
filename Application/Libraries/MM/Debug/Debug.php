<?php

namespace MM\Debug;

use MM;

class Core extends MM\Plugin
{	
	private $_eventStack;
	
	private $_stats = array();
	
	public function init()
	{
		MM\Core::register('post-init', function() {
			$requestPath = explode('/', MM\Core::getInstance()->getRequest()->getPath());
			if ($requestPath[1] == 'mm_debug') {
				
				$pathinfo = pathinfo($requestPath[2]);

				switch($pathinfo['extension']) {
					case 'js':
						header('Content-Type: application/javascript');
						break;
					case 'css':
						header('Content-Type: text/css');
						break;
					default:
						break;
				}
				
				exit(require("static/{$requestPath[2]}"));
			}
			
		}, PHP_INT_MAX);

		MM\Core::register('pre-render', function() {
			$stats = MM\Debug\Core::getInstance()->getStats();

			$stats['files'] 		= get_included_files();
			$stats['extensions'] 	= get_loaded_extensions();
			$stats['callstack'] 	= MM\Core::getInstance()->getEventManager()->getCallstack();
			
			foreach($stats['files'] as $index => $file) {
				if (strpos($file, 'MM/Debug') !== false) {
					unset($stats['files'][$index]);
				}
			}

			$response = MM\Core::getInstance()->getResponse()->getBody();
			
			// Find end of head
			$pos 	= strpos($response, '</body>');
			$end 	= substr($response, 0, $pos);
			$rest 	= substr($response, $pos);
			
			// Collect memory usage stats
			// MM\Profiler::add('rendering');

			// $stats['profiler'] = MM\Profiler::getDataSeries();

			// Insert call to debug js
			MM\Core::getInstance()->getResponse()->setBody($end . 
				'<script type="text/javascript">mmdebug_stats = ' . json_encode($stats) . '</script>' .
				'<script type="text/javascript" src="/mm_debug/debug.js"></script>' .
				$rest);
			
		}, PHP_INT_MAX);
	}
	
	public function getStats()
	{
	   	return $this->_stats;
	}
}

class EventManager extends \MM\EventManager
{
	private $_callStack = array();
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function trigger($event, array $input = array(), array &$return = null)
	{
		$backtrace = debug_backtrace();

		$this->_callstack[] = array($event => array(
			'file' => $backtrace[2]['file'],
			'line' => $backtrace[2]['line'],
			'class' => $backtrace[2]['class'],
			'function' => $backtrace[2]['function'],
			'eventstack' => $this->_stack,
		));

		//MM\Profiler::add($event . "-" . count($this->_callstack));
		
		parent::trigger($event, $input, $return);
	}
	
	public function getCallstack()
	{
	   return $this->_callstack;
	}
}