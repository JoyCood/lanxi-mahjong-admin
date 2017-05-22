<?php
return array(
    // 'host' => '127.0.0.1',
    'host'        => '120.77.175.1',
    'name'        => 'lanxi_db',
    'port'        => 27017,
    'connect'     => true,
    'collections' => array(
        'admin',
        'agent_agent_notice',   //公告
        'agent_user',           //代理商
        'col_trade_record',     //交易记录
        'agent_rebate_record',
        'admin_log',
        'user', //玩家
        'authorize', //验证码
        'user_relate', //绑定关系
	    'agent_rebate_record', //提现记录
    )
);
