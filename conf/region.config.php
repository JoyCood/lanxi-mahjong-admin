<?php
define('DEBUG', true);
class Config
{
	const SESSION_USER  = 'lanxi-region-user';
	const SESSION_GROUP = 'lanxi-region-group';

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
				'title'         => '区域管理',
				'listRowsNum'   => 30,
				'slim'          => array(
										'templates.path' => DOC_ROOT. '/view/region'
								),
				'sessionSecret' => 'a0e0eba2c41369c5797dbb3ab158b9e6',
				'resVersion'    => 1,
				'res3rdVersion' => 1
			),

			'log' => array(
				'dir'  => DOC_ROOT. '/logs/region',
				'file' => 'log-'. date('Y-m-d'). '.log',
			),

			'notauth' => array(
				'/region/login',
				'/region/logout',
			),

			'routes' => array(
				'/region/'           => 'GET::AdminController::indexAction',
				'/region/login'      => 'GET::AdminController::loginAction',
				'/region/login-auth' => 'POST::AdminController::loginAuthAction',
				'/region/logout'     => 'POST::AdminController::logoutAction',
			)
		);
	}
}