<?php 
require(DOC_ROOT. '/control/BaseController.php');

class PlayerController extends BaseController {
    const WEIXIN_BASE_URL     = 'https://api.weixin.qq.com';
	const WEIXIN_USER_INFO    = self::WEIXIN_BASE_URL . '/sns/userinfo?';
	const WEIXIN_ACCESS_TOKEN = self::WEIXIN_BASE_URL . '/sns/oauth2/access_token?';
	const WEIXIN_FRESH_TOKEN  = self::WEIXIN_BASE_URL . '/sns/oauth2/fresh_token?';

	protected function getAccessTokenAction($code) {
		$appid = Config::get('core', 'wx.app.id');
		$secret = Config::get('core', 'wx.app.secret');
		$tokenURL  = self::WEIXIN_ACCESS_TOKEN . "appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
		return Helper::curl($tokenURL);
	}

	protected function refreshTokenAction($refreshToken) {
		$appid = Config::get('core', 'wx.app.id');
        $url = self::WEIXIN_FRESH_TOKEN . "appid={$appid}&grant_type=refresh_token&refresh_token={$refreshToken}";	
		return Helper::curl($url);
	}

	protected function getUserInfoAction($openid, $accessToken) {
		$url = self::WEIXIN_USER_INFO . "access_token={$accessToken}&openid={$openid}";
        return Helper::curl($url);        	
	}

	protected function getUserByCode($code) {
		$tokenInfo = $this->getAccessTokenAction($code);
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
		if(isset($userInfo['errcode'])) {
		    $this->responseJSON($userInfo);
		}
		$userInfo['access_token'] = $tokenInfo['access_token'];
		return $userInfo;
	}

	//根据token获取用户信息
	protected function getUserByToken($accessToken) {
		$filters = array('Access_token' => $accessToken);
		$Auth = Admin::model('auth.main');
		$auth = $Auth->findOne($filters);
	    if(!$auth) {
			$data = array('errcode'=> 40014, 'errmsg'=> 'invalid access_token');
		    $this->responseJSON($data);	
		}
		$userInfo = $this->getUserInfoAction($auth['Openid'], $accessToken);
		$userInfo = json_decode($userInfo, true);
		$userInfo['access_token'] = $accessToken;

		if(isset($userInfo['errcode'])) { 
			if($userInfo['errcode'] == 42001) { //access_token超时
				$freshToken = $this->refreshTokenAction($auth['Refresh_token']);
				$freshToken = json_decode($freshToken, true);
				if(isset($freshToken['errcode'])) { //刷新TOKEN失败 42002==fresh_token超时
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
				$userInfo['access_token'] = $freshToken['access_token'];
			} else { //其它错误,原封不动返回给客户端
			    $this->responseJSON($userInfo);
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
		$params = $this->request->post();
		if(!isset($params['deviceId'])) {
		    $params['deviceId'] = 'deviceId';
		}
		if(!isset($params['deviceName'])) {
		    $params['deviceName'] = 'deviceName';
		}

        if(!isset($params['code']) && !isset($params['accessToken'])) {
            $response = array('errcode'=>44002, 'errmsg'=>'empty post data');
            $this->responseJSON($response);
        } else {
            if(isset($params['code'])) {
                $params['code'] = trim($params['code']);
            } else {
                $params['accessToken'] = trim($params['accessToken']);
            }
        }

		if(isset($params['code'])) {
		    $userInfo = $this->getUserByCode($params['code']);
		} else {
			$userInfo = $this->getUserByToken($params['accessToken']);
        }
		$accessToken = $userInfo['access_token'];
		$ip = sprintf('%u', ip2long(Admin::getRemoteIP()));
		$ip *= 1;
		$User = Admin::model('user.main');

		$filters = array(
		    'Wechat_uid' => $userInfo['openid']
		);
		$update = array('$set' => array(
		    'Nickname'        => (string)$userInfo['nickname'], 
			'Wechat_unionid'  => $userInfo['unionid'],
			'Sex'             => $userInfo['sex'],
			'Sign'            => '',
			'Photo'           => (string)$userInfo['headimgurl'],
			'Last_login_time' => time(),
			'Last_Login_ip'   => $ip
		));
		$options = array('new' => true);
		$user = $User->findAndModify($filters, $update, null, $options);
	    if($user===null) {
			$user = $this->registerAction($userInfo);
		}
		$time = time();
		$sign = Config::getOptions('game-server-sign');
		$token = md5("{$sign}{$user['_id']}{$time}{$user['Create_time']}");
	    $clientIp = Admin::getRemoteIP();
		$result = $this->apply_ip("1", $user['_id'], $clientIp, "CN", "12", $params['deviceId'], $params['deviceName']);
        
	    $userData['userid']     = $user['_id'];
		$userData['nickname']   = $user['Nickname'];
		$userData['email']      = $user['Email'];
		$userData['phone']      = $user['Phone'];
		$userData['sex']        = $user['Sex'];
		$userData['status']     = $user['Status'];
		$userData['online']     = false;
		$userData['exp']        = 0;
		$userData['ip']         = $user['Create_ip']; 
		$userData['photo']      = $user['Photo'];
		$userData['address']    = $user['Address'];
		$userData['createtime'] = $user['Create_time'];
		$userData['sign']       = $user['Sign'];
		$userData['birth']      = time();
		$userData['terminal']   = $user['Terminal'];
		$userData['coin']       = 0;
		$userData['roomtype']   = 0;
		$userData['roomid']     = 0;
		$userData['invitecode'] = '';
		$userData['diamond']    = 0;
		$userData['exchange']   = 0;
		$userData['ticket']     = 0;
		$userData['vip']        = 0;
		$userData['win']        = 0;
		$userData['lost']       = 0;
		$userData['ping']       = 0;
		$userData['platform']   = 1;
		$userData['rupt']       = 0;
		$userData['sound']      = false;
		$userData['roomcard']   = $user['RoomCard'];
		$userData['build']      = '';
		$userData['token']      = $token;
		$userData['accessToken'] = $accessToken;
		$userData['timestamp']   = time();
		$userData['serverIp']    = "{$result[0]}:8005";

		$this->renderJSON($userData);	
    }

	//注册
	protected function registerAction($userInfo) {
		$ip = sprintf('%u', ip2long(Admin::getRemoteIP()));
		$ip *= 1;
		$id = (string)Admin::model('sequence.main')->nextSequence('userId');	
		$User = Admin::model('user.main');
		$user = array(
			'_id'             => $id,
			'Nickname'        => trim($userInfo['nickname']),
			'Sex'             => $userInfo['sex'],
			'Sign'            => '',
			'Email'           => '',
			'Phone'           => isset($userInfo['phone'])? $userInfo['phone'] : '',
			'Auth'            => '',
			'Pwd'             => isset($userInfo['pwd'])? $userInfo['pwd'] : '',
			'Birth'           => '',	
			'Create_ip'       => $ip, 
			'Create_time'     => time(),
			'Coin'            => 0,
			'Exp'             => 0,
			'Diamond'         => 0,
			'Ticket'          => 0,
			'Exchange'        => 0,
			'Terminal'        => '',
			'Status'          => $User::STATUS_NORMAL,
			'Address'         => '',
			'Photo'           => isset($userInfo['headimgurl'])? $userInfo['headimgurl']: '',
			'Qq_uid'          => '',
			'Wechat_uid'      => isset($userInfo['openid'])? $userInfo['openid']: '',
			'Wechat_unionid'  => isset($userInfo['unionid'])? $userInfo['unionid']: '',
			'Microblog_uid'   => '',
			'Vip'             => 0,
			'Win'             => 0,
			'Lost'            => 0,
			'Ping'            => 0,
			'Platform'        => isset($userInfo['platform'])? $userInfo['platform'] : $User::PLATFORM_WECHAT, 
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
			'Last_Login_ip'   => $ip 	
		);    
		$User->insert($user);
		return $user;
	}

	protected function apply_ip($project_id, $user_id, $ip, $country, $area, $device_id, $device_name)
	{
        if(DEVELOPMENT) {
            return array('120.77.175.1'); //todo 开发服ip地址可放到配置文件中
        } 
		$message = 'YY';
		$message .= pack("CNN", $project_id, $user_id, ip2long($ip));
		$message .= pack('C', strlen($country)).$country;
		$message .= pack('n', $area);
		$message .= pack('C', strlen($device_id)).$device_id;
		$message .= pack('C', strlen($device_name)).$device_name;
		$ip_bin = pack("N", ip2long($ip));
		// echo "SEND...\n";
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Could not create  socket\n");
		$connection = socket_connect($socket, "192.168.1.2", 6677) or die("Could not connet server\n");
		socket_write($socket, $message) or die("Write failed\n");
		$len = socket_read($socket, 1, PHP_NORMAL_READ);
		$recv = socket_read($socket, ord($len), PHP_NORMAL_READ);
		socket_close($socket);
		return explode("|", $recv);
	}	

    public function phoneLoginAction() {
        $phone = trim($this->request->post('phone'));
        $password = trim($this->request->post('password')); 
        $deviceId = trim($this->request->post('deviceId', 'deviceId'));
        $deviceName = trim($this->request->post('deviceName', 'deviceName'));

        /*
        $phone = '13533332421';
        $password = '123456';
         */

        if(!$phone) {
            $response = array(
                'errcode' => 10000,
                'errmsg'  => '请输入手机号码'
            );
            $this->responseJSON($response);
        }
        if(!$password) {
            $response = array(
                'errcode' => 10001,
                'errmsg' => '请输入密码'
            );
            $this->responseJSON($response);
        }

        $User = Admin::model('user.main');
        $filters = array('Phone' => $phone);
        $user = $User->findOne($filters);
        if(!$user) {
            $response = array(
                'errcode' => 10002,
                'errmsg'  => '用户不存在，请先注册'
            );
            $this->responseJSON($response);
        }
        if($user['Pwd'] != md5($password)) {
            $response = array(
                'errcode' => 10003,
                'errmsg'  => '密码错误'
            );
            $this->responseJSON($response);
        }
        
		$time = time();
		$sign = Config::getOptions('game-server-sign');
		$token = md5("{$sign}{$user['_id']}{$time}{$user['Create_time']}");
	    $clientIp = Admin::getRemoteIP();
		$result = $this->apply_ip("1", $user['_id'], $clientIp, "CN", "12", $deviceId, $deviceName);
        
	    $userData['userid']     = $user['_id'];
		$userData['nickname']   = $user['Nickname'];
		$userData['email']      = $user['Email'];
		$userData['phone']      = $user['Phone'];
		$userData['sex']        = $user['Sex'];
		$userData['status']     = $user['Status'];
		$userData['online']     = false;
		$userData['exp']        = 0;
		$userData['ip']         = $user['Create_ip']; 
		$userData['photo']      = $user['Photo'];
		$userData['address']    = $user['Address'];
		$userData['createtime'] = $user['Create_time'];
		$userData['sign']       = $user['Sign'];
		$userData['birth']      = time();
		$userData['terminal']   = $user['Terminal'];
		$userData['coin']       = 0;
		$userData['roomtype']   = 0;
		$userData['roomid']     = 0;
		$userData['invitecode'] = '';
		$userData['diamond']    = 0;
		$userData['exchange']   = 0;
		$userData['ticket']     = 0;
		$userData['vip']        = 0;
		$userData['win']        = 0;
		$userData['lost']       = 0;
		$userData['ping']       = 0;
		$userData['platform']   = 1;
		$userData['rupt']       = 0;
		$userData['sound']      = false;
		$userData['roomcard']   = $user['RoomCard'];
		$userData['build']      = '';
		$userData['token']      = $token;
		$userData['accessToken'] = '';
		$userData['timestamp']   = time();
		$userData['serverIp']    = "{$result[0]}:8005";

		$this->renderJSON($userData);	
    }

    public function phoneRegAction() {
        $phone      = trim($this->request->post('phone'));
        $nickname   = trim($this->request->post('nickname'));
        $password   = trim($this->request->post('password'));
        $password2  = trim($this->request->post('password2'));
        $deviceId   = trim($this->request->post('deviceId', 'deviceId'));
        $deviceName = trim($this->request->post('deviceName', 'deviceName'));
/*
        $phone = '13533332421';
        $password = '123456';
        $password2 = '123456';
 */
        if(!$phone) {
            $response = array(
                'errcode' => 10000,
                'errmsg'  => '请填写手机号'
            );
            $this->responseJSON($response);
        }

        if(!$nickname) {
            $response = array(
                'errcode' => 10001,
                'errmsg'  => '请填写昵称'
            );
            $this->responseJSON($response);
        }

        if(!$password) {
            $response = array(
                'errcode' => 10002,
                'errmsg'  => '请填写密码'
            );
            $this->responseJSON($response);
        }

        if($password != $password2) {
            $response = array(
                'errcode' => 10003,
                'errmsg' => '密码不一致，请重新输入'
            );
            $this->responseJSON($response);
        }
        $User = Admin::model('user.main'); 
        $filters = array('Phone'=>$phone);    
        $user = $User->findOne($filters);
        if($user) {
            $response = array(
                'errcode' => 10004,
                'errmsg'  => '此号码已注册，请直接登录'
            );
            $this->responseJSON($response);
        }
        $userInfo = array(
            'nickname' => $nickname,
            'sex'      => 3,
            'phone'    => $phone,
            'pwd'      => md5($password),
            'platform' => $User::PLATFORM_PHONE
        );
        $user = $this->registerAction($userInfo);

		$time = time();
		$sign = Config::getOptions('game-server-sign');
		$token = md5("{$sign}{$user['_id']}{$time}{$user['Create_time']}");
	    $clientIp = Admin::getRemoteIP();
		$result = $this->apply_ip("1", $user['_id'], $clientIp, "CN", "12", $deviceId, $deviceName);
        
	    $userData['userid']     = $user['_id'];
		$userData['nickname']   = $user['Nickname'];
		$userData['email']      = $user['Email'];
		$userData['phone']      = $user['Phone'];
		$userData['sex']        = $user['Sex'];
		$userData['status']     = $user['Status'];
		$userData['online']     = false;
		$userData['exp']        = 0;
		$userData['ip']         = $user['Create_ip']; 
		$userData['photo']      = $user['Photo'];
		$userData['address']    = $user['Address'];
		$userData['createtime'] = $user['Create_time'];
		$userData['sign']       = $user['Sign'];
		$userData['birth']      = time();
		$userData['terminal']   = $user['Terminal'];
		$userData['coin']       = 0;
		$userData['roomtype']   = 0;
		$userData['roomid']     = 0;
		$userData['invitecode'] = '';
		$userData['diamond']    = 0;
		$userData['exchange']   = 0;
		$userData['ticket']     = 0;
		$userData['vip']        = 0;
		$userData['win']        = 0;
		$userData['lost']       = 0;
		$userData['ping']       = 0;
		$userData['platform']   = 1;
		$userData['rupt']       = 0;
		$userData['sound']      = false;
		$userData['roomcard']   = $user['RoomCard'];
		$userData['build']      = '';
		$userData['token']      = $token;
		$userData['accessToken'] = '';
		$userData['timestamp']   = time();
		$userData['serverIp']    = "{$result[0]}:8005";

		$this->renderJSON($userData);	
    }

}
