<?php
require(DOC_ROOT. '/control/BaseController.php');

class AdminController extends BaseController {
    public function indexAction() {
        $this->render('index.html');
    }

    public function loginAction() {
        $this->render('login.html', array(
            'from' => $this->request->get('from', '')
        ));
    }

    public function loginAuthAction() {
        $Admin    = Admin::model('admin.main');
        $username = trim($this->request->post('username'));
        $password = $this->request->post('password');
        
        $user = $Admin->getUserByUsername($username);
        if(!$user && $username == $Admin::DEFAULT_USER) {
            // 第一次登录时，添加默认管理员帐号
            $user = $Admin->addDefaultUser();
        }
        if($user) {
            if($user['Status'] && $Admin->password($password) == $user['Password']) {
                // 登录成功
                $_SESSION[Config::SESSION_UID]   = $user['id'];
                $_SESSION[Config::SESSION_USER]  = $user['Username'];
                $_SESSION[Config::SESSION_GROUP] = $user['Group'];
                $Admin->update(array(
                    'LastLogin' => time(),
                    'LastIP'    => Admin::getRemoteIP()
                ), $user['id']);
                $this->renderJSON(array('result' => true));
            } else {
                $this->error('密码不正确或帐号已经被停用。');
            }
        }
        $this->error('密码不存在或帐号已经被停用。');
    }

    public function logoutAction() {
        session_destroy();
        $this->renderJSON(array('result' => true));
    }

    public function logAction() {
        $data = Admin::model('admin.log')->pagination();
		$this->render('admin/log/index.html', array(
		    'data' => $data
		));
    }
}