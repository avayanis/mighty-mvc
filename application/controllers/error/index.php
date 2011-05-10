<?php

class Error_Index_Controller {
    
    public function Error_Action() {
        # clear response output
        MM::$output = '';

        $view = new SK_View('error/error');
        $view->error = MM::get_error();

        echo $view->render();
    }
}