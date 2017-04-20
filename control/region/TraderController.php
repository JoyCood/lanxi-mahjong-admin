<?php 
require(DOC_ROOT. '/control/BaseController.php');

class TraderController extends BaseController {
    public function indexAction() {
        $this->render('index.html');
    }

    public function loginAction() {
        $this->render('login.html', array(
            'from' => $this->request->get('from', '')	
        ));
    }

    public function loginAuthAction() {
        $Trader = Admin::model('Trader.main');    
        $phone  = trim($this->request->post('phone'));
        $pwd    = trim($this->request->post('password'));
            
        $filters = array(
            'Phone' => $phone, 
            'Pwd'   => md5($pwd)
        );
        $user = $Trader->findOne($filters);
        if(!$user) {
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
        $_SESSION[Config::SESSION_UID]  = $user['_id'];
        $_SESSION[Config::SESSION_USER] = $user['Nickname'];
        $filters = array('_id' => $user['_id']);
        $doc = array(
            'LIP' => Admin::getRemoteIP(), 
            'LTime' => time()
        );
        $result = $Trader->update($filters, $doc);
        $this->renderJSON(array('result' => true));
    }

    public function registerAction() {
        $Trader   = Admin::model('Trader.main');
        $nick     = trim($this->request->post('nick'));
        $gameId   = trim($this->request->post('gameId'));
        $wechat   = trim($this->request->post('wechat'));
        $phone    = trim($this->request->post('phone'));
        $authCode = trim($this->request->post('authCode')); 
        $password = trim($this->request->post('password'));
        $password2= trim($this->request->post('password2'));

        $nick = 'joy';
        $gameId = '1234';
        $wechat = '13533332421';
        $phone  = '13533332421';
        $authCode = '32145';
        $password = '123456';
        $password2 = '123456';

        if(!$nick) {
            $this->error('请填写昵称');
        }
        if(!$gameId) {
            $this->error('请填写游戏ID');
        }
        if(!$wechat) {
            $this->error('请填写微信ID');
        }
        if(!$phone) {
            $this->error('请填写手机号');
        }
        if(!$authCode) {
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
        $player = Admin::model('User.main')->findOne($filters);
        if(!$player) {
            $this->error('游戏ID不存在');
        }

        $AuthCode = Admin::model('authcode.main');
        $filters = array('Phone'=>$phone, 'Code'=>$authcode);
        $auth = $AuthCode->findOne($filters);
        if(!$auth) {
            $this->error('验证码无效，请重新获取验证码');
        }
        if(time()-$auth['CTime']>$AuthCode::AUTHCODE_EXPIRE) {
            $this->error('验证码已过期，请重新获取验证码');
        }

        $doc = array(
            'Gameid'   => $gameId,
            'Phone'    => $phone,
            'Nickname' => $nick,
            'Wechat'   => $wechatId,
            'Pwd'      => md5($password),
            'CIP'      => Admin::getRemoteIP(),
            'CTime'    => time(),
            'JTime'    => time(),
            'LIP'      => Admin::getRemoteIP(),
            'LTime'    => time(),
            'Lv'       => 0,
            'Parent'   => 0,
            'Charge'   => 0,
            'Status'   => 0,
            'Balance'  => 0,
            'Addr'     => ''
        );
        $Trader->insert($doc);
        $this->renderJSON();
    }

    public function getAuthcodeAction() {
        $phone = trim($this->request->post('phone'));
        if(!Phone::validation($phone)) {
            $this->error('请填写正确的手机号码');
        }
        $AuthCode = Admin::model('authcode.main');
        $filters = array('phone' => $phone);
        $auth = $AuthCode->findOne($filters);
        if(!$auth) {
            $authCode = substr(mt_rand(), -6);
            
            $doc = array(
                'Phone' => $phone,
                'Code'  => $authCode,
                'CTime' => time()
            );
            $AuthCode->insert($doc);
            Admin::send($authCode);
            return;
        }

        if(time()-$auth['CTime']>$AuthCode::AUTHCODE_EXPIRE) {
            $authCode      = substr(mt_rand(), -6);
            $auth['Code']  = $authCode;
            $auth['CTime'] = time();
            $AuthCode->update($filters, $auth);
            Phone::send($authCode);
            return;
        }

        Phone::send($auth['code']);
    }
}
