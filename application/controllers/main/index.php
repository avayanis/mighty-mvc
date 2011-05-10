<?php

class Main_Index_Controller {

    public function Index_Action() {

        $view = new MM_View('main/index');
 
        echo $view->render();
    }
}
