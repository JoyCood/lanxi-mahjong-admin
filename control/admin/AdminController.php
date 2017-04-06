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
}