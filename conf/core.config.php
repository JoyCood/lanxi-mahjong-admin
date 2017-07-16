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

//支付宝支付回调URL
define('DEV_ALIPAY_NOTIFY_URL', DEV_BASE_URL. '/api/notify/alipay');
define('PRO_ALIPAY_NOTIFY_URL', PRO_BASE_URL. '/api/notify/alipay');

return array(
    'lx.trader.rate'     => 0.5, //给代理商的返现比例
	'lx.base.url'        => DEBUG ? DEV_BASE_URL: PRO_BASE_URL,

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

	'alipay.app.id'      => '2017060907453495',
	'alipay.format'      => 'json',
	'alipay.charset'     => 'UTF-8',
	'alipay.sign.type'   => 'RSA2',
	'alipay.gateway.url' => 'https://openapi.alipay.com/gateway.do',
	'alipay.timeout.express' => '30m',
	'alipay.notify.url'  => DEBUG ? DEV_ALIPAY_NOTIFY_URL: PRO_ALIPAY_NOTIFY_URL,
	'alipay.private.key' => 'MIIEowIBAAKCAQEA2uzn7ClGoCbjba79qUuDvCxYXPQc0xeuIvFShOpJ1I9bclgXijXkaRaeopw4i6l6AAj9Gfredx9UUAwzpJXD7Upbl+yMTyAPo+cPLkyYesgL/xTeLMMLOp/fVwOcIesam3nh+BSo7tKTcJ7bVrmYhQrtLRdHIOcQrR2Lqm8VhkuUFthGugkf0oB34RTyHUkxdsd++yy/ePmBpLbhhb4W3L2rNju0mplq90qAkRJ8LoXgUcSbYfguU271+mHG7jOkjyRoo8hWmxqwDmnX5Y6rj571lAAG1/CtsNLCLb7llk8Dh/AHjC8vNv9IxzPQYRAKKt1m9MT0rVghbpOLsMaXiQIDAQABAoIBAG0PZ9n2FgDiXpndevBDdjTfwoVL9rASM+WkYn9kV7NUVzH3QILRph/T7OMFAAZTPzuVPsEagIjHSrE14uQy14/2YvqkFKG2TxRPnvhpn+xKi+9e7e9OmuFxk0k9uyIKhQ6/ARsc76tJq7mG6a2id6CPeOflmMGDWoNjHJijDg3/3FTTAsqXLwf5SyfpFIWDIJa+zdXhksbiO1azz8O95JkjY4F/ONzgeH/ZbIL6/6weeNgFrUasRSVCLLMOQq6V3ZRpk0nJtdrNP3wzI+FOCO9qr52ITy/G7cO2QlfXdTB/yK7XqehE9VROv/L6tLxZqkMkLAW68qz4GTsyxRLDXoECgYEA+zWxvkK+p1yzS5wH3fFzWYlTEeN9+IwffkjH4qnm4SOgldPNm6VzChmZ91LILX6gPuf0oltSepeNGOPTmIY3uJCoBwjItxHxwZJJs0VzHdWIqjK90wyR0jf112R20uEDM6cNAnT3e+p/wXUJdZRKimWa5/a2l0ZJBszXaDQ3BGUCgYEA3xmcyBe88IgNonFuNl62Phl+7cb0RsPJf25h/butvir32TA5kv9dSZc6ofytxqIcatmrzIQ4VZCKl1q4KkpvH6pfNtwywC699Z/sdAGjKGAqwH6d4EzsFMlQStchdHEB2CtoMG/9MUALsjbrgHHzjbi54JW+oddi3MEhkgkielUCgYEAzpPAH6p5Z92WX5Xm7j9w0O9ZjXtbCvoHp+yn2yBGiVun4a20BcuPh324HCdKEAdyNWi/xod8ku1vjUlEpsewqKjkVCyICevKt5jZ3H8/BAimAvgJG7XZcFquYrrt78ozvNUhoKSZI6CaAHeDGNqiVZ7qPNioHEwjH2FxvfI6T/UCgYAJlu8/Ro3vhNmZLuj+20lYF/51uASN+iJyIMuDfYq1TuGCa2k+IU3gOOjjR5osnJE+IocY7mIBg4oBgXvDDZoDMW6QTBy+WAQAR9SBFATwWlncFlUw6/2F0zVpUh/1AlqC8TK0SkwGzurxyRyMU9mVMOiEd8tO55QvSvnnbg8WVQKBgF2j6HZOMl71d9f2jWDh5cMKL7Nl1yJYxU8SAzN99yJGHd/T827XRfCGJTbQW+T7zsCdEdH1mIvx362k6pTyNXIVMiTkIVS49r8dJj4Z2kIgVTL2Hm0/OJCgXAwBhew/aNiLRsbhrSR+QPF8XuW5Nszmf3YyjvDx4t96ji3znWdp',
	'alipay.public.key'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuOSbK/cQ4nKmq3zPtclbHOEgFmgYJxTuZIabH44qlMSzGqByKks2G/wv1mMDKFCg4ce3luPO5YO+mVMqwaYbkp7mh+dnHRwc0K9eR4dIMuFmdCucbHtYEuV/UQE/VVsvkooTbqnnbIShS67f/xYnqUQ7kOvDC6TArsGGRbwfOHsGQqNFO4b59xAytFfS9OM4QcgEuo1I2ZQQOCe4C+OG186afmkjsUZj2tlNvaR3rRn9ODo1eu+eXgi6vBLPF5k81BHwLiFE8L0QJuBBiRugAVjb/Vz/aVcG+FZ48LuF2PpT6l04iYvdpUjzd4JWQZMTmuGEjPIONAuC58S3QZL/6QIDAQAB',
);
