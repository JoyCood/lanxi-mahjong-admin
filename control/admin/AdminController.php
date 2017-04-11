<?php
require(DOC_ROOT. '/control/BaseController.php');
class AdminController extends BaseController{
    public function indexAction() {
        echo 'hello'; exit;
    }

    public function loginAction() {
        $this->render('login.html');
    }

    public function loginAuthAction() {

    }

    public function logoutAction() {
    }

    public function logAction() {
        $data = Admin::model('admin.log')->pagination();
		$this->render('admin/log/index.html', array(
		    'data' => $data
		));
    }
}