<?php
define('DEBUG', true);

class Config {
    static private $Options;
    
    const SESSION_UID = 'lanxi-api-user-id';
    const SESSION_USER = 'lanxi-api-user';
	const SESSION_GROUP = 'lanxi-api-group';

	const GAME_SERVER_HOST = 'http://yiiyu.cn:7229/roomcard';
	const GAME_SERVER_SIGN = 'XG0e2Ye/KAUJRXaMNnJ5UH1haBvh2FXOoAggE6f2Utw';
	const WEIXIN_APP_ID = 'wxa0f7643705d54733';
	const WEIXIN_SECRET = '2046430fc5a9b9ea50ab89285d6d17c6';

    static function init() {
        self::$Options = array(
            //数据库
            'db' => require('db.config.php'), 
            //房卡
            'goods' => require('goods.config.php'),
            //支付
            'payment' => require('pay.config.php'), 
			//游戏服务器地址
			'game-server-host' => 'http://lanxi.yiiyu.cn:7229/roomcard',
            //游戏服务器通讯密钥
			'game-server-sign' => 'XG0e2Ye/KAUJRXaMNnJ5UH1haBvh2FXOoAggE6f2Utw"',
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
                'dir' => DOC_ROOT .  '/logs/api',
                'file' => 'log-'. date('Y-m-d'). '.log',
            ),

            //免登录 
            'notauth' => array(
				'/api/app',
                '/api/goods',
                '/api/wechat-login',
				'/api/place-order',
            ),

            //路由设置
			'routes' => array(
				'/api/app' => 'GET::ShareController::appAction',
                '/api/wechat-login'  => 'POST::PlayerController::wechatLoginAction',
                '/api/goods'  => 'GET::GoodsController::listAction',
                '/api/place-order' => 'GET::PaymentController::placeOrderAction'
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
