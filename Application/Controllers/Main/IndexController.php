<?php

namespace Main;

use MM;

class IndexController
{
	public function IndexAction()
	{
		$view = new MM\View("requirements");
	
		echo $view->render();
	}
}