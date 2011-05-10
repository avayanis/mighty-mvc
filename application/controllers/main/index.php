<?php

class Main_Index_Controller {

    public function Index_Action() {

        $view = new MM _View('main/index');
 
        echo $view->render();
    }
}
