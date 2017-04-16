<?php
define('DEBUG', true);
class Config
{
	static private $Options;

	const SESSION_UID   = 'lanxi-admin-user-id';
	const SESSION_USER  = 'lanxi-admin-user';
	const SESSION_GROUP = 'lanxi-admin-group';

	static public function init() {
		self::$Options = array(
			// 数据库
			'db' => require('db.config.php'),
			// 网站设置
			'settings' => array(
				'title'         => '后台管理',
				'listRowsNum'   => 30,
				'slim'          => array(
										'templates.path' => DOC_ROOT. '/view/admin'
								),
				'sessionSecret' => '61789127738e4799de0e2bd462d5a10b',
				'resVersion'    => 1,
				'res3rdVersion' => 1
			),
			// 日志
			'log' => array(
				'dir'  => DOC_ROOT. '/logs/admin',
				'file' => 'log-'. date('Y-m-d'). '.log',
			),
			// 免登录
			'notauth' => array(
				'/admin/login',
				'/admin/login-auth',
				'/admin/logout',
			),
			// 路径设置
			'routes' => array(
				'/admin/'           => 'GET::AdminController::indexAction',
				'/admin/login'      => 'GET::AdminController::loginAction',
				'/admin/login-auth' => 'POST::AdminController::loginAuthAction',
				'/admin/logout'     => 'POST::AdminController::logoutAction',
				'/admin/log'        => 'GET::AdminController::logAction',
				// 订单
				'/admin/money/inpour'        => 'GET::MoneyController::inpourAction',
				'/admin/money/inpour/form'   => 'GET::MoneyController::inpourFormAction',
				'/admin/money/withdraw'      => 'GET::MoneyController::withdrawAction',
				'/admin/money/withdraw/form' => 'GET::MoneyController::withdrawFormAction',
				// 公告
				'/admin/notice/'          => 'GET::NoticeController::indexAction',
				'/admin/notice/form'      => 'GET::NoticeController::formAction',
				'/admin/notice/form-save' => 'POST::NoticeController::formSaveAction',
				'/admin/notice/delete'    => 'POST::NoticeController::deleteAction',
				// 代理商
				'/admin/trader/'          => 'GET::TraderController::indexAction',
				'/admin/trader/list'      => 'GET::TraderController::listAction',
				'/admin/trader/form'      => 'GET::TraderController::formAction',
				'/admin/trader/form-save' => 'POST::TraderController::formSaveAction',
				'/admin/trader/delete'    => 'POST::TraderController::deleteAction',
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