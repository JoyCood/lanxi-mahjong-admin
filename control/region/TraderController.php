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

    //登录
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
    
    //注册表单页面
    public function regAction() {
        $this->render('reg.html');     
    }

    //注册
    public function registerAction() {
        $Trader   = Admin::model('Trader.main');
        $nick     = trim($this->request->post('nick'));
        $gameId   = trim($this->request->post('gameId'));
        $wechat   = trim($this->request->post('wechat'));
        $phone    = trim($this->request->post('phone'));
        $code     = trim($this->request->post('code')); 
        $password = trim($this->request->post('password'));
        $password2= trim($this->request->post('password2'));

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
        $player = Admin::model('User.main')->findOne($filters);
        if(!$player) {
            $this->error('游戏ID不存在');
        }
        $AuthCode = Admin::model('authcode.main');
        $filters = array('Phone'=>$phone, 'Code'=>$code);
        $auth = $AuthCode->findOne($filters);
        if(!$auth) {
            $this->error('验证码无效，请重新获取验证码');
        }
        if(time()-$auth['CTime']>$AuthCode::AUTHCODE_EXPIRE) {
            $this->error('验证码已过期，请重新获取验证码');
        }
        $filters = array( 
            '$or' => array( 
                array('Gameid' => $gameId), 
                array('Phone' => $phone)
        ));
        $trader = $Trader->findOne($filters);
        if($trader) {
            $this->error('你已经是代理商，请直接登录');
        }

        $doc = array(
            'Gameid'   => $gameId,
            'Phone'    => $phone,
            'Nickname' => $nick,
            'Wechat'   => $wechat,
            'Pwd'      => md5($password),
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
            Admin::model('trader.relate')->update(
                array('GameId' => $gameId),
                array('Agent'  => 1)
            ); 
        }
        $this->renderJSON((boolean)$result);
    }

    //获取验证码
    public function getAuthcodeAction() {
        $phone = trim($this->request->post('phone'));
        if(!Phone::validation($phone)) {
            $this->error('请填写正确的手机号码');
        }
        $AuthCode = Admin::model('authcode.main');
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
            $msg = "您的验证码是:{$code}【趣游泳】";
            $result = Phone::send($phone, $msg);
            return;
        }

        if(time()-$auth['CTime']>$AuthCode::AUTHCODE_EXPIRE) {
            $code = substr(mt_rand(), -6);
            $auth['Code']  = $code;
            $auth['CTime'] = time();
            $AuthCode->update($filters, $auth);
            $msg = "您的验证码是:{$code}【趣游泳】";
            Phone::send($phone, $msg);
            return;
        }

        $msg = "您的验证码是:{$auth['code']}【趣游泳】";
        Phone::send($phone, $msg);
    }
}
