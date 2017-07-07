<?php
require(DOC_ROOT. '/control/BaseController.php');

class ShareController extends BaseController {
    public function downloadAction() {
        $this->render('app.html', array(
            'release' => Config::getOptions('release')
        ));	
	}
}
