<?php
define('DEBUG', true);

class Config {
    static private $Options;
    
    const SESSION_UID = 'lanxi-api-user-id';
    const SESSION_USER = 'lanxi-api-user';
	const SESSION_GROUP = 'lanxi-api-group';

    //新注册用户赠送的房卡数量
    const INIT_ROOM_CARD = 1000;

    //CLIENT-KEY
    const CLIENT_KEY = '9hK200FSCXZx_321/78F84ERxop2qbMT';

	//逻辑服信息，购买房卡时用于通知游戏服务器发货结果
	const GAME_SERVER_HOST = 'http://101.37.97.86:7229/roomcard';
	const GAME_SERVER_SIGN = 'XG0e2Ye/KAUJRXaMNnJ5UH1haBvh2FXOoAggE6f2Utw';

	const DEV_SERVER_HOST  = '120.77.175.1'; //测试环境游戏服务器IP
	const GAME_SERVER_PORT = 8005; //游戏服务器端口

	//分配逻辑服IP的服务器地址
	const GAME_IP_SERVER_HOST = '192.168.1.2';
    const GAME_IP_SERVER_PORT = 6677;

    const BROADCAST_ENABLED   = TRUE; //是否显示公告栏跑马灯
    const PHONE_LOGIN_ENABLED = TRUE; //是否允许手机号登录
    const PHONE_REG_ENABLED   = TRUE; //是否允许手机注册
    const APPLE_PAY_ENABLED   = TRUE; //是否打开苹果内购支付
    const BIND_TRADER_ENABLE  = TRUE; //是否打开绑定代理商弹窗 

    static function init() {
        self::$Options = array(
            //数据库
            'db' => require('db.config.php'), 
            //房卡
            'card' => require('card.config.php'),
            //支付、登录
            'core' => require('core.config.php'), 
            //网站设置
            'settings' => array(
                'slim' => array(
                    'templates.path' => DOC_ROOT. '/view/api'
                ),
                'resVersion' => 1,
                'res3rdVersion' => 1
            ),
            //日志
            'log' => array(
                'dir'  => '/var/log/lanxi-mahjong/api',
                'file' => 'log-'. date('Y-m-d'). '.log',
            ),

            //免登录 
			'notauth' => array(
				'/api/notice',
                '/api/card',
                '/api/wechat-login',
				'/api/wxpay',
                '/api/phone-reg',
                '/api/phone-login',
                '/api/notify/wechat',
                '/api/toggle',
                '/api/bind-trader'
            ),

            //路由设置
			'routes' => array(
				'/api/notice'        => 'POST::SystemController::broadcastAction',
                '/api/wechat-login'  => 'POST::PlayerController::wechatLoginAction',
                '/api/card'          => 'POST::CardController::listAction',
                '/api/wxpay'         => 'POST::CardController::wxPayAction',
                '/api/phone-reg'     => 'POST::PlayerController::phoneRegAction',
                '/api/phone-login'   => 'POST::PlayerController::phoneLoginAction',
                '/api/notify/wechat' => 'POST::CardController::wxPayNotifyAction',
                '/api/toggle'        => 'POST::SystemController::toggleAction',
                '/api/bind-trader'   => 'POST::PlayerController::bindTraderAction'
            )
        ); 
    }

    static public function & get($key, $field) {
        return self::$Options[$key][$field];
    }

    static public function & getOptions($key) {
        return self::$Options[$key];
    }
}
