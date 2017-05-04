<?php 
require(DOC_ROOT. '/control/BaseController.php');

class PlayerController extends BaseController {
    
    //登录游戏
    public function wechatLoginAction() {
		$params = $this->request->post('data');
        $params = Helper::decodeParams($params);         
		if(!$params) {
			$response = Helper::encodeParams(array('code' => 20001));
			$this->renderJSON(array('data'=>$response));
			exit();
		}

		$User = Admin::model('user.main');

		$filters = array(
		    'Wechat_uid' => $params['uid']
		);
		$update = array(
		    'Nickname'        => (string)$params['nick'], 
			'Sex'             => (string)$params['sex'],
			'Sign'            => (string)$params['sign'],
			'Photo'           => (string)$params['photo'],
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
}
