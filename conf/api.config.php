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
	const GAME_SERVER_HOST     = 'http://101.37.97.86:7229/roomcard';
    const DEV_GAME_SERVER_HOST = 'http://120.77.175.1:7229/roomcard';
	const GAME_SERVER_SIGN     = 'XG0e2Ye/KAUJRXaMNnJ5UH1haBvh2FXOoAggE6f2Utw';
	const GAME_SERVER_PORT     = 8005; //游戏服务器端口

	const DEV_SERVER_HOST  = '120.77.175.1'; //测试环境游戏服务器IP

	//分配逻辑服IP的服务器地址
	const GAME_IP_SERVER_HOST = '192.168.1.2';
    const GAME_IP_SERVER_PORT = 6677;

	const RELEASE_NUMBER = 1;
    const BROADCAST_ENABLED    = TRUE; //是否显示公告栏跑马灯
    const PHONE_LOGIN_ENABLED  = TRUE; //是否允许手机号登录
    const PHONE_REG_ENABLED    = TRUE; //是否允许手机注册
    const BIND_TRADER_ENABLE   = TRUE; //是否打开绑定代理商弹窗 

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
            //版本
            'release' => require('release.config.php'),
            //免登录 
			'notauth' => array(
				'/api/notice',        # 跑马灯公告
                '/api/card',          # 房卡列表
                '/api/wechat-login',  # 微信登录
				'/api/wxpay',         # 微信下单
                '/api/phone-reg',     # 手机注册
                '/api/phone-login',   # 手机登录
                '/api/notify/wechat', # 微信支付回调发货
                '/api/notify/iap',    # 苹果支付发货
                '/api/toggle',        # 开关控制
                '/api/bind-trader',   # 绑定代理商
                '/api/version-check', # 版本检测
				'/api/alipay',        # 支付宝下单
            ),

            //路由设置
			'routes' => array(
				'/api/notice'        => 'POST::SystemController::broadcastAction',   # 跑马灯公告
                '/api/card'          => 'POST::CardController::listAction',          # 房卡列表
                '/api/wechat-login'  => 'POST::PlayerController::wechatLoginAction', # 微信登录
                '/api/wxpay'         => 'POST::CardController::wxPayAction',         # 微信下单
                '/api/phone-reg'     => 'POST::PlayerController::phoneRegAction',    # 手机注册
                '/api/phone-login'   => 'POST::PlayerController::phoneLoginAction',  # 手机登录
                '/api/notify/wechat' => 'POST::CardController::wxPayNotifyAction',   # 微信支付回调发货
                '/api/notify/iap'    => 'POST::CardController::IAPNotifyAction',     # 苹果支付发货
                '/api/toggle'        => 'POST::SystemController::toggleAction',      # 开关控制
                '/api/bind-trader'   => 'POST::PlayerController::bindTraderAction',  # 绑定代理商
                '/api/version-check' => 'POST::SystemController::versionCheckAction',# 版本检测
				'/api/alipay'        => 'POST::CardController::alipayAction'         # 支付宝下单
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
