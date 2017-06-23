<?php
define('DEBUG', true);
class Config
{
    static private $Options;

    const SESSION_UID   = 'lanxi-region-user-id';
    const SESSION_USER  = 'lanxi-region-user';
    const SESSION_GROUP = 'lanxi-region-group';

    static public function & get($key, $field) {
        return self::$Options[$key][$field];
    }

    static public function & getOptions($key) {
        return self::$Options[$key];
    }

    static public function init() {
        self::$Options = array(
                // 数据库
                'db' => require('db.config.php'),
                // 房卡
                'card' => require('card.config.php'),
				//支付、登录
				'card' => array(
					'1' => array(
						'Id' => '1',
						'Title' => '房卡50张',
						'Cardnum' => 50,
						'Money' => 50,
						'Default' => false,
					),
					'2' => array(
						'Id' => '2',
						'Title' => '房卡100张',
						'Cardnum' => 100,
						'Money' => 95,
						'Default' => true,
					),
					'3' => array(
						'Id' => '3',
						'Title' => '房卡200张',
						'Cardnum' => 200,
						'Money' => 180,
						'Default' => false,
					),
				),
                // 网站设置
                'settings' => array(
                        'title'         => '代理商系统',
                        'listRowsNum'   => 30,
                        'slim'          => array(
                            'templates.path' => DOC_ROOT. '/view/region'
                         ),
                        'csrfProtection' => true,
                        'sessionName'    => 'LXRSESSION',
                        'sessionSecret'  => 'a0e0eba2c41369c5797dbb3ab158b9e6',
                        'resVersion'     => 5,
                        'res3rdVersion'  => 5
                ),
                // 日志
                'log' => array(
                        'dir'  => '/var/log/lanxi-mahjong/region',
                        'file' => 'log-'. date('Y-m-d'). '.log',
                ),
                // 免登录
                'notauth' => array(
					    '/region/wechat-login',
                        '/region/login',
                        '/region/logout',
                        '/region/code',
                        '/region/register',
                        '/region/agreement',
                        '/region/pwd',
                        '/region/wechatCR'
                ),
                // 路径设置
                'routes' => array(
                        '/region/'                    => 'GET::TraderController::indexAction',
						'/region/wechat-login'        => 'GET::TraderController::wechatLoginAction',
                        '/region/login'               => '::TraderController::loginAction',
                        '/region/agreement'           => 'GET::TraderController::agreementAction',
                        '/region/logout'              => 'POST::TraderController::logoutAction',       //登出
                        '/region/code'                => 'POST::TraderController::getAuthcodeAction',  //获取验证码
                        '/region/register'            => '::TraderController::registerAction',         //注册
			            '/region/trader/list'         => 'GET::TraderController::listAction',          //我的下级代理列表
                        '/region/money/withdraw/form' => 'GET::MoneyController::withdrawFormAction',   //提现
                        '/region/money/withdraw'      => 'POST::MoneyController::withdrawAction',      //提现
			            '/region/money/log'           => 'GET::MoneyController::withdrawLogAction',    //提现记录表列
						'/region/pwd'                 => '::TraderController::resetPwdAction',         //重置密码
						'/region/recharge'            => '::CardController::rechargeAction',           //代理商给玩家充房卡
						'/region/custom-recharge'     => '::CardController::customRechargeAction',     //代理商批发购买房卡
                        '/region/wechatCR'            => 'GET::CardController::wechatCustomeRechargeAction', //从微信公众号批发购买房卡
						'/region/recharge/user'       => '::CardController::userAction',               //充房卡查询用户信息
                )
        );
    }
}
