<?php
define('DEBUG', true);
define('AdminTemplatePath', DOC_ROOT . '/view/region');
define('LogDir', DOC_ROOT . '/logs/region');

class Config
{
    public static $session = array(
        'USER'  => 'lanxi_region_user',
		'GROUP' => 'lanxi_region_group',
    );
    
    public static $admin = array(
        'title'         => '区域代理',
		'listRowsNum'   => 30,
	
		'slim'          => array(
								'templates.path' => AdminTemplatePath
						   ),
		'sessionSecret' => '91a02ac42f99738aa6d2dc0738a0ae56',
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
		'/region/login',
		'/region/logout'
	);

	public static $routes = array(
		'/region/' => 'GET::AdminController::indexAction'
	);
}
