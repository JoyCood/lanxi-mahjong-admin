<?php 
require(DOC_ROOT. '/control/BaseController.php');

class PlayerController extends BaseController {
    const WEIXIN_BASE_URL     = 'https://api.weixin.qq.com';
	const WEIXIN_USER_INFO    = self::WEIXIN_BASE_URL . '/sns/oauth2/userinfo?';
	const WEIXIN_ACCESS_TOKEN = self::WEIXIN_BASE_URL . '/sns/oauth2/access_token?';
	const WEIXIN_FRESH_TOKEN  = self::WEIXIN_BASE_URL . '/sns/oauth2/fresh_token?';

	protected function getAccessTokenAction($code) {
		$appid = Config::WEIXIN_APP_ID;
		$secret = Config::WEIXIN_SECRET;
		$tokenURL  = self::WEIXIN_ACCESS_TOKEN . "appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
		return Helper::curl($tokenURL);
	}

	protected function refreshTokenAction($refreshToken) {
		$appid = Config::WEIXIN_APP_ID;
        $url = self::WEIXIN_FRESH_TOKEN . "appid={$appid}&grant_type=refresh_token&refresh_token={$refreshToken}";	
		return Helper::curl($url);
	}

	protected function getUserInfoAction($openid, $accessToken) {
		$url = self::WEIXIN_USER_INFO . "access_token?access_token={$accessToken}&openid={$openid}";
        return Helper::curl($url);        	
	}

	protected function getUserByCode($code) {
		$tokenInfo = $this->getAccessTokenAction($code);
		$this->log->debug($tokenInfo);
        $tokenInfo = json_decode($tokenInfo, true);
		if(isset($tokenInfo['errcode'])) {
		    return $tokenInfo;
		}
        $auth = Admin::model('auth.main');
		$filters = array(
			'Openid'  => $tokenInfo['openid'],
			'Unionid' => $tokenInfo['unionid'], 
			'Channel' => $auth::CHANNEL_WEIXIN
		);
		$update  = array(
			'Openid'        => $tokenInfo['openid'],
			'Unionid'       => $tokenInfo['unionid'],
			'Access_token'  => $tokenInfo['access_token'],
			'Refresh_token' => $tokenInfo['refresh_token']
		);
		$auth->findAndModify($filters, $update);
		$userInfo = $this->getUserInfoAction($tokenInfo['openid'], $tokenInfo['access_token']);
		$userInfo = json_decode($userInfo, true);
		//todo 判断refresh_token是否已过期，过期了才刷新
		$this->refreshTokenAction($userInfo['fresh_token']);
		return $userInfo;
	}

	//根据token获取用户信息
	protected function getUserByToken($accessToken) {
		$filters = array('Access_token' => $accessToken);
		$Auth = Admin::model('auth.main');
		$auth = $Auth->findOne($filters);
		$userInfo = $this->getUserInfoAction($auth['Openid'], $accessToken);
		$userInfo = json_decode($userInfo, true);
		if(isset($userInfo['errcode']) && $userInfo['errcode'] == 42001) { //access_token超时
			$freshToken = $this->refreshTokenAction($auth['Refresh_token']);
			if(isset($freshToken['errcode'])) { //刷新TOKEN失败
				return $freshToken;
			}
			$filters = array(
				'Channel' => $auth::CHANNEL_WEIXIN,
				'Openid'  => $freshToken['openid'],
				'Unionid' = $freshToken['unionid'],
			);
			$update  = array(
				'Access_token'  => $freshToken['access_token'],
				'Refresh_token' => $freshToken['fresh_token']
			);
			$auth->update($filters, $update);
			$userInfo = $this->getUserInfoAction($freshToken['openid'], $freshToken['access_token']);
			$userInfo = json_decode($userInfo, true);
		}

		return $userInfo;
	}

    //登录游戏
    public function wechatLoginAction() {
		/*
		$params = $this->request->post('data');
        $params = Helper::decodeParams($params);         
		if(!$params) {
			$response = Helper::encodeParams(array('code' => 20001));
			$this->renderJSON(array('data'=>$response));
			exit();
		}
		*/
		$params = $this->request->post('data');
		//$params['code'] = '081ft7Do0FHO1p1ierEo0TPSCo0ft7Dg';
		$this->log->debug(json_encode($params));
		if(isset($params['code'])) {
		    $userInfo = $this->getUserByCode($params['code']);
		} else if(isset($params['access_token'])) {
			$userInfo = $this->getUserByToken($params['access_token']);
		}
		$this->log->debug(json_encode($userInfo));
		exit();
		$User = Admin::model('user.main');

		$filters = array(
		    'Wechat_uid' => $userInfo['openid']
		);
		$update = array(
		    'Nickname'        => (string)$userInfo['nick'], 
			'Sex'             => (string)$userInfo['sex'],
			'Sign'            => (string)$userInfo['sign'],
			'Photo'           => (string)$userInfo['photo'],
			'Last_login_time' => time(),
			'Last_Login_ip'   => Admin::getRemoteIP(),
		);
		$options = array('new' => true);
		$user = $User->findAndModify($filters, $update, null, $options);
	    if($user===null) {
		    $user = array(
		        '_id'             => (string)Admin::model('sequence')->nextSquence('userId'),	
				'Nickname'        => trim($params['nick']),
			    'Sign'            => trim($params['sign']),
			    'Email'           => '',
			    'Phone'           => '',
			    'Auth'            => '',
			    'Pwd'             => '',
			    'Birth'           => '',	
				'Create_ip'       => (string)Admin::getRemoteIP(),
				'Create_time'     => time(),
				'Coin'            => 0,
				'Exp'             => 0,
				'Diamond'         => 0,
				'Ticket'          => 0,
				'Exchange'        => 0,
				'Terminal'        => '',
				'Status'          => $User::STATUS_NORMAL,
				'Address'         => '',
				'Photo'           => trim($params['photo']),
				'Qq_uid'          => '',
				'Wechat_uid'      => trim($params['uid']),
				'Microblog_uid'   => '',
				'Vip'             => 0,
				'Win'             => 0,
				'Lost'            => 0,
				'Ping'            => 0,
				'Platform'        => $User::PLATFORM_WECHAT, 
				'ChenmiTime'      => 0,
				'Chenmi'          => 0,
				'Sound'           => true,
				'Robot'           => false,
				'RoomCard'        => $User::INIT_ROOM_CARD,
			    'Build'           => '',
			    'BuildTime'       => 0,
			    'FyAccountId'     => '',
			    'FyAccountPwd'    => '',
			    'IsTrader'        => $User::PLAYER,
			    'Last_login_time' => time(),
			    'Last_Login_ip'   => (string)Admin::getRemoteIP()	
			);    
			$User->insert($user);
		}	
		$response = Helper::encodeParams(array('code'=>0, 'data' => $user));
		$this->renderJSON(array('data' => $response));	
    }

	public function deviceAction() {
	
	}
}
