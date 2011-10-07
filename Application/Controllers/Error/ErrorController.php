<?php

class Error_ErrorController {
	
	public function ErrorAction() {
		// clear response
		MM::$response = '';

		switch (MM::$status) {
			case 404:
				$view = new MM_View('error/404');
				break;
			case 500:
			default:
				$view = new MM_View('error/500');
				$view->error = MM::getError();
				break;
		}

		echo $view->render();
	}
}