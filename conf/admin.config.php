<?php
define('DEBUG', true);
define('AdminTemplatePath', DOC_ROOT . '/view/admin');
define('LogDir', 'logs/admin');

class Config
{
    public static $session = array(
        'USER'  => 'lanxi_admin_user',
		'GROUP' => 'lanxi_admin_group',
    );
    
    public static $admin = array(
        'title'         => '后台管理',
		'listRowsNum'   => 30,
	
		'slim'          => array(
								'templates.path' => AdminTemplatePath
						   ),
		'sessionSecret' => '61789127738e4799de0e2bd462d5a10b',
		'resVersion'    => 1,
		'res3rdVersion' => 1
    );

    //数据库表
    public static $db = array(
		'DB'         => DB_NAME,
		'collection' => array(
			'admin'
		) 
    );

	public static $notauth = array(
		'/admin/login',
		'/admin/logout'
	);

	public static $routes = array(
		'/admin/'           => 'GET::AdminController::indexAction',
		'/admin/login'      => 'GET::AdminController::loginAction',
		'/admin/login-auth' => 'POST::AdminController::loginAuthAction',
		'/admin/logout'     => 'POST::AdminController::logoutAction',
	);
}
