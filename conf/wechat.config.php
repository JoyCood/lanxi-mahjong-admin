<?php
define('DEBUG', true);

class Config {
    static private $Options;
    
    const SESSION_UID = 'lanxi-api-user-id';
    const SESSION_USER = 'lanxi-api-user';
	const SESSION_GROUP = 'lanxi-api-group';

	const BIND_TRADER_ENABLE = TRUE; //是否打开绑定代理商功能

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
                    'templates.path' => DOC_ROOT. '/view/wechat'
                ),
				'sessionName' => 'lanxi_wechat_session',
                'resVersion' => 1,
                'res3rdVersion' => 1
            ),
            //日志
            'log' => array(
                'dir' => '/var/log/lanxi-mahjong/wechat',
                'file' => 'log-'. date('Y-m-d'). '.log',
            ),

            //免登录 
            'notauth' => array(
				'/wechat/download',
				'/wechat/login',
				'/wechat/recharge',
				'/wechat/recharge/user',
                '/wechat/wxpay',
				'/wechat/bind-trader'
            ),

            //路由设置
			'routes' => array(
				'/wechat/download'      => 'GET::ShareController::downloadAction',
				'/wechat/login'         => 'GET::CardController::loginAction',
				'/wechat/recharge'      => 'GET::CardController::rechargeAction',
                '/wechat/recharge/user' => 'POST::CardController::userAction',
                '/wechat/wxpay'         => 'POST::CardController::wxPayAction',
				'/wechat/bind-trader'   => 'POST::CardController::bindTraderAction'
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
