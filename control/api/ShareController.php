<?php
require(DOC_ROOT. '/control/BaseController.php');

class ShareController extends BaseController {
    public function appAction() {
        $this->render('app.html');	
	}
}
