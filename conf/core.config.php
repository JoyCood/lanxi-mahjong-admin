<?php
//测试公众号
define('DEV_MP_ID', 'wxa355e293aeb139a7');
define('DEV_MP_SECRET', 'ba47c5b500faab762392195db56f5dcb');

//运营公众号
define('PRO_MP_ID', 'wxd70a955fe9efa1e8');
define('PRO_MP_SECRET', 'be1934227f8fbbb34b630f70d83e9d42');

//测试服URL
define('DEV_BASE_URL', 'http://yiiyu.cn/lanxi-dev');

//运营服URL
define('PRO_BASE_URL', 'http://yiiyu.cn/lanxi');

//微信支付回调URL
define('DEV_WX_NOTIFY_URL', DEV_BASE_URL. '/api/notify/wechat');
define('PRO_WX_NOTIFY_URL', PRO_BASE_URL. '/api/notify/wechat');

return array(
    'wx.mp.id'           => DEBUG ? DEV_MP_ID: PRO_MP_ID, 
    'wx.mp.secret'       => DEBUG ? DEV_MP_SECRET: PRO_MP_SECRET, 
	'wx.mp.mch.id'       => '1480206112', //公众号支付商户号
    'wx.app.id'          => 'wxa0f7643705d54733', 
    'wx.app.secret'      => '2046430fc5a9b9ea50ab89285d6d17c6',
    'wx.app.key'         => 'aY842DgklmhwvHIOnze860HnxziiM412', 
    'wx.mch.id'          => '1476411702', //APP支付商户号 
    'wx.sslcert.path'    => '/usr/local/paykey/wxpay/apiclient_cert.pem',
    'wx.sslkey.path'     => '/usr/local/paykey/wxpay/apiclient_key.pem',
    'wx.curl.proxy.host' => '0.0.0.0',
    'wx.curl.proxy.port' => '0',
    'wx.report.level'    => 1,
    'wx.notify.url'      => DEBUG ? DEV_WX_NOTIFY_URL: PRO_WX_NOTIFY_URL,
    'lx.trader.rate'     => 0.5, //给代理商的返现比例
    'lx.base.url'        => DEBUG ? DEV_BASE_URL: PRO_BASE_URL
);
