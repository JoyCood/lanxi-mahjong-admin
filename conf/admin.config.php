<?php
define('DEBUG', true);
class Config
{
	const SESSION_USER  = 'lanxi-admin-user';
	const SESSION_GROUP = 'lanxi-admin-group';

	static private $Options;

	static public function & get($key, $field) {
		return self::$Options[$key][$field];
	}

	static public function & getOptions($key) {
		return self::$Options[$key];
	}

	static public function init() {
		self::$Options = array(
			'db' => require('db.config.php'),

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

			'log' => array(
				'dir'  => DOC_ROOT. '/logs/admin',
				'file' => 'log-'. date('Y-m-d'). '.log',
			),

			'notauth' => array(
				'/admin/login',
				'/admin/logout',
			),

			'routes' => array(
				'/admin/'           => 'GET::AdminController::indexAction',
				'/admin/login'      => 'GET::AdminController::loginAction',
				'/admin/login-auth' => 'POST::AdminController::loginAuthAction',
				'/admin/logout'     => 'POST::AdminController::logoutAction',
			)
		);
	}
}