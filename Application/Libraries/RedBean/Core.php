<?php

namespace RedBean;

use MM;

class Core extends MM\Plugin
{
	private $_config;

	public function init()
	{
		$config = ($temp = MM\Core::getInstance()->config('redbean')) ? $temp : array();
		$this->setConfig($config);

		spl_autoload_register(array($this, 'autoload'));
	}

	public function setConfig(array $config = array())
	{
		$this->_config = $config;
	}

	public function autoload($class)
	{
		if (strpos('RedBean', $class) == 0 || $class == "R") {
			require __DIR__ . MM_DS . 'rb.php';
			
			// Expects config array to match RedBean setup/setupMultiple requirements.
			\R::setup($this->getConfig());

			spl_autoload_unregister(array($this, 'autoload'));
		}
	}

	public static function _libraries()
	{
		return array();
	}
}