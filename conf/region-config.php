<?php !defined('TANG_FENG') AND exit('Access Denied!');

define('AdminTemplatePath', DOC_ROOT . '/view/region');

class Config
{
    public static $session = array(
        'REGION_USER'  => 'lanxi_username',
		'REGION_GROUP' => 'lanxi_group',
    );
    
    public static $admin = array(
        'title' => '网站标题',
		'listRowsNum' => 30,
	
		'slim' => array(
	    	'templates.path' => AdminTemplatePath
        ),
		'sessionSecret' => '91a02ac42f99738aa6d2dc0738a0ae56',
		'resVersion' => 1,
		'res3rdVersion' => 1
    );

    //数据库表
    public static $db = array(
	'DB' => 'lanxi',
	'collection' => array(
	    'admin'
	) 
    );
}
