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
                //房卡(批发价)
				'card' => array(
					'1' => array(
						'Id' => '1',
						'Title' => '房卡150张',
						'CardNum' => 150,
						'Money' => 180,
						'Default' => false,
					),
					'2' => array(
						'Id' => '2',
						'Title' => '房卡330张',
						'CardNum' => 330,
						'Money' => 360,
						'Default' => true,
					),
					'3' => array(
						'Id' => '3',
						'Title' => '房卡900张',
						'CardNum' => 900,
						'Money' => 900,
						'Default' => false,
					)
                ),
                //支付，登录
                'core' => require('core.config.php'),
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
                        '/region/wechatCR',
						'/region/wxpay',
                        '/region/invite/user',
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
						'/region/custom-recharge'     => '::CardController::customRechargeAction',     //代理商给玩家充房卡
                        '/region/wechatCR'            => 'GET::CardController::wechatCustomeRechargeAction', //从微信公众号批发购买房卡
                        '/region/wxpay'               => 'POST::CardController::wxPayAction',                //微信支付批发房卡
						'/region/recharge/user'       => '::CardController::userAction',               //充房卡查询用户信息
                        '/region/alipayCR'            => 'GET::CardController::alipayCustomeRecargeAction', //通过浏览器批发房卡
						'/region/alipay'              => 'POST::CardController::alipayAction',          //支付宝wap支付
                        '/region/alipay-result'       => 'GET::CardController::alipayResultAction',     //支付宝wap支付结果展示
                        '/region/invite/user'         => 'GET::TraderController::inviteUserAction',     //邀请用户
                        '/region/invite/trader'       => 'GET::TraderController::inviteTraderAction',   //邀请代理商
                )
        );
    }
}
