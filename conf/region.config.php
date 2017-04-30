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
                // 网站设置
                'settings' => array(
                        'title'         => '区域管理',
                        'listRowsNum'   => 30,
                        'slim'          => array(
                            'templates.path' => DOC_ROOT. '/view/region'
                         ),
                        'sessionSecret' => 'a0e0eba2c41369c5797dbb3ab158b9e6',
                        'resVersion'    => 1,
                        'res3rdVersion' => 1
                ),
                // 日志
                'log' => array(
                        'dir'  => DOC_ROOT. '/logs/region',
                        'file' => 'log-'. date('Y-m-d'). '.log',
                ),
                // 免登录
                'notauth' => array(
                        '/region/login',
                        '/region/logout',
                        '/region/code',
                        '/region/register',
                        '/region/agreement',
                ),
                // 路径设置
                'routes' => array(
                        '/region/'               => 'GET::TraderController::indexAction',
                        '/region/login'          => '::TraderController::loginAction',
                        '/region/agreement'      => 'GET::TraderController::agreementAction',
                        '/region/logout'         => 'POST::TraderController::logoutAction',       //登出
                        '/region/code'           => 'POST::TraderController::getAuthcodeAction',  //获取验证码
                        '/region/register'       => '::TraderController::registerAction',         //注册
			            '/region/trader/list'    => 'GET::TraderController::listAction',          //我的下级代理列表
                        '/region/money/withdraw' => 'GET::MoneyController::withdrawAction',       //提现
			            '/region/money/log'      => 'POST::MoneyController::withdrawLogAction'    //提现记录表列
                )
        );
    }
}
