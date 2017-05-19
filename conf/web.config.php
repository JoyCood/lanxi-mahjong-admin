<?php
define('DEBUG', true);

class Config {
    static private $Options;
    
    const SESSION_UID = 'lanxi-api-user-id';
    const SESSION_USER = 'lanxi-api-user';
	const SESSION_GROUP = 'lanxi-api-group';

	//公众号
	const MP_APP_ID = 'wxd70a955fe9efa1e8';
	const MP_SECRET = 'be1934227f8fbbb34b630f70d83e9d42';

    static function init() {
        self::$Options = array(
            //数据库
            'db' => require('db.config.php'), 
            //房卡
            'goods' => require('goods.config.php'),
            //支付
            'payment' => require('pay.config.php'), 
            //网站设置
            'settings' => array(
                'slim' => array(
                    'templates.path' => DOC_ROOT. '/view/web'
                ),
                'resVersion' => 1,
                'res3rdVersion' => 1
            ),
            //日志
            'log' => array(
                'dir' => DOC_ROOT .  '/logs/web',
                'file' => 'log-'. date('Y-m-d'). '.log',
            ),

            //免登录 
            'notauth' => array(
				'/web/app',
                '/web/goods',
				'/web/place-order',
				'/web/wechat-test'
            ),

            //路由设置
			'routes' => array(
				'/web/app' => 'GET::ShareController::appAction',
                '/web/goods'  => 'GET::GoodsController::listAction',
                '/web/place-order' => 'GET::PaymentController::placeOrderAction',
				'/web/wechat-test' => 'GET::PlayerController::testAction'
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
