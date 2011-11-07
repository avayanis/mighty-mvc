<?php

namespace Twig;

use MM;

class Core extends MM\Plugin
{
	private $_config;
	private $_environment;
	private $_loader;

	public function init()
	{
		$defaults = array(
			'loaderInterface' => '\Twig_Loader_Filesystem',
			'loader' => array(
				'template_dir' => MM_APP_PATH . MM_DS . 'Views',
			),
			'environment' => array(
				'cache' => MM_APP_PATH . MM_DS . 'Cache' . MM_DS . 'Twig',	
			)
		);

		$config = ($temp = MM\Core::getInstance()->config('twig')) ? array_merge($defaults, $temp) : $defaults;
		$this->setConfig($config);
	}

	public function setConfig(array $config = array())
	{
		$this->_config = $config;
	}

	public function getConfig($section = null)
	{
		if ($section) {
			return (isset($this->_config[$section])) ? $this->_config[$section] : null;
		}

		return $this->_config;
	}

	public function getLoader()
	{
		if (!$this->_loader) {
			require __DIR__ . MM_DS . 'Autoloader.php';
			
			\Twig_Autoloader::register();

			$config = $this->getConfig();
			$this->setLoader(new $config['loaderInterface'](
				$config['loader']
			));
		}

		return $this->_loader;
	}

	public function setLoader(\Twig_LoaderInterface $loader)
	{
		$this->_loader = $loader;
	}

	public function getEnvironment()
	{
		if (!$this->_environment) {
			$this->setEnvironment(
				new \Twig_Environment($this->getLoader(), $config['environment'])
			);
		}

		return $this->_environment;
	}

	public function setEnvironment($environment)
	{
		$this->_environment = $environment;
	}

	public static function _libraries()
	{
		return array();
	}
}

class Template
{
	public static function get($templateName)
	{
		$environment = Core::getInstance()->getEnvironment();
	}
}