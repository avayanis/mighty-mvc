<?php

class Main_IndexController {

    public function IndexAction() {

        $view = new MM_View('Main/Index');
 
        echo $view->render();
    }
}
