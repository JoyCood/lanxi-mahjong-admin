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
		$code = '061dxXHS1QlJya1ow3HS1AGdIS1dxXHc';
		$tokenInfo = $this->getAccessTokenAction($code);
	    $this->log->debug($tokenInfo);
        $tokenInfo = json_decode($tokenInfo, true);
		if(isset($tokenInfo['errcode'])) {
			$this->responseJSON($tokenInfo);
		}
        $Auth = Admin::model('auth.main');
		$filters = array(
			'Openid'  => $tokenInfo['openid'],
			'Unionid' => $tokenInfo['unionid'], 
			'Channel' => $Auth::CHANNEL_WEIXIN
		);
		$data  = array(
			'Channel'       => $Auth::CHANNEL_WEIXIN,
			'Phone'         => '',
			'Code'          => '',
			'CTime'         => time(),
			'Openid'        => $tokenInfo['openid'],
			'Unionid'       => $tokenInfo['unionid'],
			'Access_token'  => $tokenInfo['access_token'],
			'Refresh_token' => $tokenInfo['refresh_token']
		);
		$auth = $Auth->findOne($filters);
		if(!$auth) {
			$Auth->insert($data);
		} else {
		    unset($data['CTime']);
			$Auth->update($filters, $data);
		}

		$userInfo = $this->getUserInfoAction($tokenInfo['openid'], $tokenInfo['access_token']);
		$userInfo = json_decode($userInfo, true);
		//todo 判断refresh_token是否已过期，过期了才刷新
		$this->refreshTokenAction($userInfo['refresh_token']);
		return $userInfo;
	}

	//根据token获取用户信息
	protected function getUserByToken($accessToken) {
		$filters = array('Access_token' => $accessToken);
		$Auth = Admin::model('auth.main');
		$auth = $Auth->findOne($filters);
	    if(!$auth) {
			$data = array('code'=> 40014, 'errmsg'=> 'invalid access_token');
		    $this->responseJSON($data);	
		}
		$userInfo = $this->getUserInfoAction($auth['Openid'], $accessToken);
		$userInfo = json_decode($userInfo, true);
		if(isset($userInfo['errcode'])) { 
			if($userInfo['errcode'] == 42001) { //access_token超时
				$freshToken = $this->refreshTokenAction($auth['Refresh_token']);
				$freshToken = json_decode($freshToken, true);
				if(isset($freshToken['errcode'])) { //刷新TOKEN失败
					$this->responseJSON($freshToken);
				}
				$filters = array(
					'Channel' => $auth::CHANNEL_WEIXIN,
					'Openid'  => $freshToken['openid'],
					'Unionid' => $freshToken['unionid'],
				);
				$update  = array(
					'Access_token'  => $freshToken['access_token'],
					'Refresh_token' => $freshToken['refresh_token']
				);
				$auth->update($filters, $update);
				$userInfo = $this->getUserInfoAction($freshToken['openid'], $freshToken['access_token']);
				$userInfo = json_decode($userInfo, true);
				if(isset($userInfo['errcode'])) {
					$this->responseJSON($userInfo);
				}
			} else { //其它错误,原封不动返回给客户端
			    $thisis->responseJSON($userInfo);
			}
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
