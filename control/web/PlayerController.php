<?php 
require(DOC_ROOT. '/control/BaseController.php');

class PlayerController extends BaseController {
	protected $MP_APP_ID = Config::MP_APP_ID;
	protected $MP_SECRET = Config::MP_SECRET;
	protected $MP_URL = 'https://open.weixin.qq.com';
	protected $OAUTH2_URL = 'https://api.weixin.qq.com/sns';

	protected function getCodeAction() {
		$APPID  = $this->MP_APP_ID;
		$SECRET = $this->MP_SECRET; 
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

    public function testAction() {
        $code = $this->request->get('code');	
		if(empty($code) && !isset($_SESSION['unionid'])) {
	        $this->getCodeAction();            	
		}
		if(!isset($_SESSION['access_token'])) {
		    $token = $this->getAccessTokenAction($code);
			$token = json_decode($token, true);
			$_SESSION['access_token'] = $token;
		} else {
		    $token = $_SESSION['access_token'];
		}
		
		if(isset($token['errcode'])) { 
			if($token['errcode']==40029) {
			    $this->getCodeAction();
			}
		    print_r($token);
		    exit('error');	
		}
	    print_r($token);
		$userinfo = $this->getUserInfoAction($token['openid'], $token['access_token']);
		$userinfo = json_decode($userinfo, true);
		$_SESSION['unionid'] = $userinfo['unionid'];
		print_r($userinfo);
	}
}
