<?php !defined('TANG_FENG') AND exit('Access Denied!');

define('AdminTemplatePath', DOC_ROOT . '/view');

class Config
{
    public static $session = array(
	    'REGION_ID'    => 'admin_id',
        'REGION_USER'  => 'admin_user',
	    'REGION_GROUP' => 'admin_group',
    );
    
    public static $admin = array(
        'title' => '网站标题',
	    'listRowsNum' => 30,
	
	    'slim' => array(
	        'templates.path' => AdminTemplatePath
        ),
	    'sessionSecret' => '^_^ admin ^_^',
	    'resVersion' => 1,
	    'res3rdVersion' => 1
    );

    //数据库表
    public static $db = array(
	    'DB' => 'lanxi',
	    'collection' => array(
	        'admin',
            'agent_agent_notice', //公告
			'agent_user', //代理商
			'col_trade_record', //交易记录
			'admin_log'
	    )
    );
}
