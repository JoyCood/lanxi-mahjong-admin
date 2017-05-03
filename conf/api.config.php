<?php
define('DEBUG', true);

class Config {
    static private $Options;
    
    const SESSION_UID = 'lanxi-api-user-id';
    const SESSION_USER = 'lanxi-api-user';
    const SESSION_GROUP = 'lanxi-api-group';

    static function init() {
        self::$Options = array(
            //数据库
            'db' => require('db.config.php'), 
            //房卡
            'goods' => require('goods.config.php'),
            //支付
            'payment' => require('pay.config.php'), 
			//游戏服务器地址
			'game-server-host' => 'http://localhost:7229/roomcard',
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
                '/api/goods',
                '/api/login',
                '/api/place-order'
            ),

            //路由设置
            'routes' => array(
                '/api/login'  => 'POST::PlayerController::loginAction',
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
