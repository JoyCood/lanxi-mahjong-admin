<?php
return array(
    #测试公众号id: wxa355e293aeb139a7
    #测试公众号secret: ba47c5b500faab762392195db56f5dcb

    #运营公众号id：wxd70a955fe9efa1e8
    #运营公众号secret: be1934227f8fbbb34b630f70d83e9d42
    'wx.mp.id' => DEBUG ? 'wxa355e293aeb139a7': 'wxd70a955fe9efa1e8', //运营公众号id
    'wx.mp.secret' => DEBUG ? 'ba47c5b500faab762392195db56f5dcb': 'be1934227f8fbbb34b630f70d83e9d42', //运营公众号secret
    'wx.app.id' => 'wxa0f7643705d54733', //app id
    'wx.app.secret' => '2046430fc5a9b9ea50ab89285d6d17c6', //app secret
    'wx.app.key' => '343d8b263c2aea31250a6907785bd128', 
    'wx.mch.id' => '1312998901', 
    'wx.sslcert.path' => '/usr/local/paykey/wxpay/apiclient_cert.pem',
    'wx.sslkey.path' => '/usr/local/paykey/wxpay/apiclient_key.pem',
    'wx.curl.proxy.host' => '0.0.0.0',
    'wx.curl.proxy.port' => '0',
    'wx.report.level' => 1,
    'wx.notify.url' => 'http://yiiyu.cn/lanxi/api/notify/wechat',
    'lx.trader.rate' => 0.5, //给代理商的返现比例
    'lx.base.url' => DEBUG ? 'http://yiiyu.cn/lanxi-dev' : 'http://yiiyu.cn/lanxi'
);
