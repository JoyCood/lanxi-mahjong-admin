<?php
return array(
    'host' => 'localhost',
    'port' => 27017,
    'connect' => true,
    'name' => 'lanxi',
    'collections' => array(
        'admin',
        'agent_agent_notice',   //公告
        'agent_user',           //代理商
        'col_trade_record',     //交易记录
        'admin_log',
        'user', //玩家
        'authcode', //验证码
        'user_relate', //绑定关系
		'agent_rebate_record', //提现记录
    )
);
