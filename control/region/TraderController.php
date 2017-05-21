<?php 
require(DOC_ROOT. '/control/BaseController.php');

class TraderController extends BaseController {
    public function indexAction() {
        $this->listAction();
    }

    public function loginAction() {
        if($this->request->isGet()) {
            $this->render('login.html', array(
                'from' => $this->request->get('from', '')	
            ));
        } else {
            $this->loginAuthAction();
        }
    }

    //登录
    protected function loginAuthAction() {
        $Trader = Admin::model('trader.main');    
        $phone  = trim($this->request->post('phone'));
        $pwd    = trim($this->request->post('password'));
            
        $filters = array(
            'Phone' => $phone, 
            // 'Pwd'   => md5($pwd)
        );
        $user = $Trader->findOne($filters);
        if(!$user || $user['Pwd'] != $Trader->password($pwd)) {
            $this->error('手机号或密码不正确');
        }
        switch($user['Status']) {
            case $Trader::STATUS_LOCKED:
                $this->error('对不起，您的帐号已被锁定，请联系客服人员');
            case $Trader::STATUS_BLACKLIST:
                $this->error('对不起，您的帐号已加入黑名单，请联系客服人员');
            case $Trader::STATUS_WAITING:
                $this->error('对不起，您的帐号还在等待审核，请稍后再试');
        }
        $this->userLogin($user['Gameid'], $user['Nickname']);
        $filters = array('_id' => $user['_id']);
        $doc = array(
            'LIP' => Admin::getRemoteIP(), 
            'LTime' => time()
        );
        $result = $Trader->update($filters, $doc);
        $this->renderJSON(array('result' => true));
    }
    
    //注册表单页面
    public function registerAction() {
        if($this->request->isGet()) {
            $this->render('register.html', array(
                'AuthCode' => Admin::model('auth.main')
            ));
        } else if($this->request->isPost()) {
            $this->register();
        }
    }

    //注册
    protected function register() {
        $Trader    = Admin::model('trader.main');
        $gameId    = trim($this->request->post('GameId'));
        $wechat    = trim($this->request->post('Wechat'));
        $phone     = trim($this->request->post('Phone'));
        $code      = trim($this->request->post('Captcha')); 
        $password  = trim($this->request->post('Password'));
        $password2 = trim($this->request->post('Confirm'));

        if(!$gameId) {
            $this->error('请填写游戏ID');
        }
        if(!$wechat) {
            $this->error('请填写微信ID');
        }
        if(!$phone) {
            $this->error('请填写手机号');
        }
        if(!$code) {
            $this->error('请填写验证码');
        }
        if(!$password) {
            $this->error('请填写密码');
        }
        if(!$password2) {
            $this->error('请填写确认密码');
        }

        if($password != $password2) {
            $this->error('密码不一致');
        }
        if(!Phone::validation($phone)) {
            $this->error('请填写正确的手机号码');
        }
        $filters = array('_id' => $gameId);
        $player  = Admin::model('user.main')->findOne($filters);
        if(!$player) {
            $this->error('游戏ID不存在');
        }
        $AuthCode = Admin::model('auth.main');
        $filters  = array('Phone'=>$phone, 'Code'=>$code);
        $auth = $AuthCode->findOne($filters);
        if(!$auth) {
            $this->error('验证码无效，请重新获取验证码', 10086);
        }
        if(time()-$auth['CTime']>$AuthCode::AUTHCODE_EXPIRE) {
            $this->error('验证码已过期，请重新获取验证码', 10086);
        }
        $filters = array( 
            '$or' => array( 
                array('Gameid' => $gameId), 
                array('Phone' => $phone)
        ));
        $trader = $Trader->findOne($filters);
        if($trader) {
            $this->error('你已经是代理商，请直接登录', 10086);
        }

        $doc = array(
            'Gameid'   => $gameId,
            'Phone'    => $phone,
            'Nickname' => $player['Nickname'],
            'Wechat'   => $wechat,
            'Pwd'      => $Trader->password($password),
            'CIP'      => Admin::getRemoteIP(),
            'CTime'    => time(),
            'JTime'    => time(),
            'LIP'      => Admin::getRemoteIP(),
            'LTime'    => time(),
            'Lv'       => 0,
            'Parent'   => "",
            'Charge'   => 0,
            'Status'   => 0,
            'Balance'  => 0,
            'Addr'     => ''
        );
        $result = $Trader->insert($doc);
        if($result['ok']==1) {
            Admin::model('user.main')->update(
                array('_id' => $gameId),
                array('IsTrader' => ModelUserMain::TRADER)
            ); 
        }
        $this->userLogin($gameId, $doc['Nickname']);
        $this->renderJSON((boolean)$result);
    }

    protected function userLogin($gameId, $nickname) {
        $_SESSION[Config::SESSION_UID]  = $gameId;
        $_SESSION[Config::SESSION_USER] = $nickname;
    }

    protected function userLogout() {
        session_destroy();
    }

	//重置密码
    public function resetPwdAction() {
        $AuthCode = Admin::model('auth.main');

		if($this->request->isGet()) {
			$this->render('pwd.html');	
		} else if($this->request->isPost()) {
			$phone     = trim($this->request->post('Phone')); 
		    $code      = trim($this->request->post('Captcha'));	
			$password  = trim($this->request->post('Password'));
			$password2 = trim($this->request->post('Confirm'));

			if($phone=='') {
			    $this->error('请填写手机号');
	        }
			if(!Phone::validation($phone)) {
			    $this->error('请填写正确的手机号码'); 
			}

			$filters = array('Phone' => $phone, 'Code' => $code);
			$auth = $AuthCode->findOne($filters);
			if(!$auth) {
				$this->error('验证码无效，请重新获取验证码', 10086);
			}
			if(time()-$auth['CTime']>$AuthCode::AUTHCODE_EXPIRE) {
				$this->error('验证码已过期，请重新获取验证码', 10086);
			}
		    
			if($password=='') {
			    $this->error('请填写密码');
			}
			if($password != $password2) {
			    $this->error('密码不一致');
			}

			$Trader = Admin::model('trader.main');
			$filters = array('Phone' => $phone);
			$trader = $Trader->findOne($filters);
			if(!$trader) {
			    $this->error('帐号不存在,请先注册');
			}
		    $doc = array(
			    'Pwd' => $Trader->password($password)
			);
		    $result = $Trader->update($filters, $doc);	
			$this->renderJSON((bool)$result);
		}	
	}

    //获取验证码
    public function getAuthcodeAction() {
        $phone = trim($this->request->post('phone'));
        if(!Phone::validation($phone)) {
            $this->error('请填写正确的手机号码');
        }
        $AuthCode = Admin::model('auth.main');
        $filters = array('phone' => $phone);
        $auth = $AuthCode->findOne($filters);
        if(!$auth) {
            $code = substr(mt_rand(), -6);
            
            $doc = array(
                'Phone' => $phone,
                'Code'  => $code,
                'CTime' => time()
            );
            $AuthCode->insert($doc);
            $msg = "【兰溪雀神】您的验证码是{$code}";
            $result = Phone::send($phone, $msg);
            $this->renderJSON($result);
        }

        if(time()-$auth['CTime']>$AuthCode::AUTHCODE_EXPIRE) {
            $code = substr(mt_rand(), -6);
            $auth['Code']  = $code;
            $auth['CTime'] = time();
            $AuthCode->update($filters, $auth);
            $msg = "【兰溪雀神】您的验证码是{$code}";
            $result = Phone::send($phone, $msg);
            $this->renderJSON($result);
        }

        $msg = "【兰溪雀神】您的验证码是{$auth['code']}";
        $result = Phone::send($phone, $msg);
        $this->renderJSON($result);
    }

    //我的下级代理列表
    public function listAction() {
        $Trader = Admin::model('trader.main');	
        $User   = Admin::model('user.main');	
        $pn     = $this->request->get('pn');

        $params['filters'] = array(
            'Build'    => $_SESSION[Config::SESSION_UID],
            'IsTrader' => $User::TRADER 
        );

        $traders = $User->pagination($params, $pn);
        $this->render('/trader/list.html', array(
            'data' => $traders
        ));
    }

    public function agreementAction() {
        $this->render('agreement.html');
    }

    public function logoutAction() {
        $this->userLogout();
        $this->renderJSON(true);
    }
}
