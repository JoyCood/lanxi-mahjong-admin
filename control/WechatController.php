<?php
require 'BaseController.php';

class WechatController extends BaseController {
	const MP_SESSION_OPENID = 'mp_session_openid';
	const MP_SESSION_UNIONID = 'mp_session_unionid';
	const MP_SESSION_ACCESS_TOKEN = 'mp_session_access_token';
	const MP_SESSION_REFRESH_TOKEN = 'mp_session_refresh_token';

	const MP_BASE_URL = 'https://open.weixin.qq.com';
	const MP_OAUTH2_URL = 'https://api.weixin.qq.com/sns';
    
    protected function getAppId() {
        return Config::get('core', 'wx.mp.id');
    }

    protected function getAppSecret() {
        return Config::get('core', 'wx.mp.secret');
    }

	protected function getCode($redirect) {
	    $APPID = $this->getAppId(); 
		$BASEURL = self::MP_BASE_URL;	
		$redirect = urlencode(trim($redirect));
		$url = "{$BASEURL}/connect/oauth2/authorize?appid={$APPID}&redirect_uri={$redirect}&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
		header("Location:{$url}");
	}

	protected function getAccessToken($code) {
	    $APPID = $this->getAppId();
		$SECRET = $this->getAppSecret();
		$OAUTH2_URL = self::MP_OAUTH2_URL;
		$url = "{$OAUTH2_URL}/oauth2/access_token?appid={$APPID}&secret={$SECRET}&code={$code}&grant_type=authorization_code";
		return Helper::curl($url);
	}

	protected function freshToken($refreshToken) {
        $APPID = $this->getAppId();
	    $SECRET = $this->getAppSecret();
	    $OAUTH2_URL = self::MP_OAUTH2_URL;	
		$url = "{$OAUTH2_URL}/oauth2/refresh_token?appid={$APPID}&grant_type=refresh_token&refresh_token={$refreshToken}";
		return Helper::curl($url);
	}

	protected function getUserInfo($openid, $accessToken) {
        $OAUTH2_URL = self::MP_OAUTH2_URL;
	    $url = "{$OAUTH2_URL}/userinfo?access_token={$accessToken}&openid={$openid}&lang=zh_CN";	
		return Helper::curl($url);
	}

	public function login($redirect) {
        $code = $this->request->get('code');	
		if(empty($code)) {
		    $this->getCode($redirect);
			exit();
		}
		$userinfo = array();
		if(isset($_SESSION[self::MP_SESSION_OPENID]) && isset($_SESSION[self::MP_SESSION_ACCESS_TOKEN])) {
		    $userinfo = $this->getUserInfo($_SESSION[self::MP_SESSION_OPENID], $_SESSION[self::MP_SESSION_ACCESS_TOKEN]);
			$userinfo = json_decode($userinfo, true);
			//$data = array('from'=>'session', 'userinfo'=>$userinfo);
			//$this->log->debug(json_encode($data));
		}

		if(!$userinfo) {
		    $token = json_decode($this->getAccessToken($code), true);
			$userinfo2 = array();
			if(!isset($token['errcode'])) {
		        $userinfo = $this->getUserInfo($token['openid'], $token['access_token']);
			    $userinfo = json_decode($userinfo, true);	
				$_SESSION[self::MP_SESSION_OPENID] = $token['openid'];
				$_SESSION[self::MP_SESSION_UNIONID] = $token['unionid'];
				$_SESSION[self::MP_SESSION_ACCESS_TOKEN] = $token['access_token'];
				$_SESSION[self::MP_SESSION_REFRESH_TOKEN] = $token['refresh_token'];
            }
            /*
			$data = array(
				'from'=>'token', 
				'userinfo' => $userinfo,
				'token' => $token,
			);
			$this->log->debug(json_encode($data));
             */
		}
		if(isset($userinfo['errcode'])) {
			$token2 = array();
			$userinfo2 = array();
		    if(in_array($userinfo['errcode'], array(40001, 40014, 42001))) {
			    $token = json_decode($this->getAccessToken($code), true);
				$token2 = $token;
				$userinfo = $this->getUserInfo($token['openid'], $token['access_token']);
				$userinfo = json_decode($userinfo, true);
				$userinfo2 = $userinfo;
				$_SESSION[self::MP_SESSION_OPENID] = $token['openid'];
				$_SESSION[self::MP_SESSION_UNIONID] = $token['unionid'];
				$_SESSION[self::MP_SESSION_ACCESS_TOKEN] = $token['access_token'];
				$_SESSION[self::MP_SESSION_REFRESH_TOKEN] = $token['refresh_token'];
			}
            /*
			$data = array(
				'from'=>'token_expire', 
				'userinfo'=>$userinfo, 
				'userinfo2' => $userinfo2,
				'token'=>$token,
				'token2' => $token2
			);
			$this->log->debug(json_encode($data));
             */
		}
        /*
	    $data = array('from'=>'final', 'userinfo'=>$userinfo);
		$this->log->debug(json_encode($data));
         */
        return $userinfo;
	}

}
