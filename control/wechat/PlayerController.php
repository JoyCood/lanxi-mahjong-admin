<?php 
require(DOC_ROOT. '/control/BaseController.php');

class PlayerController extends BaseController {
	protected $MP_APP_ID = Config::MP_APP_ID;
	protected $MP_SECRET = Config::MP_SECRET;
	protected $MP_URL = 'https://open.weixin.qq.com';
	protected $OAUTH2_URL = 'https://api.weixin.qq.com/sns';

	protected function getCodeAction() {
		$APPID  = $this->MP_APP_ID;
		$MP_URL = $this->MP_URL;
		$redirect_uri = urlencode('http://yiiyu.cn/lanxi/web/wechat-test');
        $url = "{$MP_URL}/connect/oauth2/authorize?appid=$APPID&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
		header("Location:{$url}");
	}

	protected function getAccessTokenAction($code) {
		$APPID  = $this->MP_APP_ID;
		$SECRET = $this->MP_SECRET;
		$OAUTH2_URL = $this->OAUTH2_URL;
        $url = "{$OAUTH2_URL}/oauth2/access_token?appid=$APPID&secret=$SECRET&code=$code&grant_type=authorization_code";
	
		return Helper::curl($url);
	}

	protected function freshTokenAction($refreshToken) {
        $APPID  = $this->MP_APP_ID;	
		$SECRET = $this->MP_SECRET;
		$OAUTH2_URL = $this->OAUTH2_URL;
        $url = "{$OAUTH2_URL}/oauth2/refresh_token?appid={$APPID}&grant_type=refresh_token&refresh_token={$refreshToken}"; 
		return Helper::curl($url);
	}

	protected function getUserInfoAction($openid, $accessToken) {
		$OAUTH2_URL = $this->OAUTH2_URL;
	    $url = "{$OAUTH2_URL}/userinfo?access_token={$accessToken}&openid={$openid}&lang=zh_CN"; 
		return Helper::curl($url);
	}

    public function loginAction() {
        $code = $this->request->get('code');	
		if(empty($code)) {
	        $this->getCodeAction();            	
		}
		$userinfo = array();
		if(isset($_SESSION['openid']) && isset($_SESSION['access_token'])) {
		    $userinfo = $this->getUserInfoAction($_SESSION['openid'], $_SESSION['access_token']);
			$userinfo = json_decode($userinfo, true);
		}
		if(!$userinfo) {
		    $token = json_decode($this->getAccessTokenAction($code), true);
			if(!isset($token['errcode'])) {
			    //$this->freshTokenAction($token['refresh_token']);
			    $userinfo = $this->getUserInfoAction($token['openid'], $token['access_token']);
				$userinfo = json_decode($userinfo, true);
				$_SESSION['openid'] = $token['openid'];
				$_SESSION['access_token'] = $token['access_token'];
			}
		}
		if(isset($userinfo['errcode'])) {
		    if(in_array($userinfo['errcode'],array(40014, 42001))) { //token超时
				$token = json_decode($this->getAccessTokenAction($code), true);
				
				//$this->freshTokenAction($token['refresh_token']);
				$userinfo = $this->getUserInfoAction($token['openid'], $token['access_token']);
				$_SESSION['openid'] = $token['openid'];
				$_SESSION['access_token'] = $token['access_token'];
			}
		}
		print_r($userinfo);
	}
}