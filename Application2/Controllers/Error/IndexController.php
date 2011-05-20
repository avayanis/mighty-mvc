<?php

class Error_IndexController {
    
    public function Error_Action() {
        # clear response output
        MM::$output = '';

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