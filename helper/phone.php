<?php !defined('TANG_FENG') AND exit('Access Denied!');

require 'lib/phonenumber/vendor/autoload.php';
require 'lib/yunpian/sms.php';

class Phone {
    static private $phonenumber; 
    static private $yunpian;

    const API_KEY = '';

    static public function init() {
        self::$phonenumber = \libphonenumber\PhoneNumberUtil::getInstance(); 
        self::$yunpian = new YunPianSms(); 
    }

    static private function parse($phone) {
        try {
            return self::$phonenumber->parse($phone, 'CN');
        }catch(\libphonenumber\NumberParseException $e) {
            return false;
        }
    }

    //校验手机号码是否合法
    static public function validation($phone) {
        $proto = self::parse($phone);
        if(!$proto) {
            return false;
        }
        return self::$phonenumber->isValidNumber($proto);
    }
    
    //发送短信
    static public function send($phone, $msg) {
        if(!self::validation($phone)) {
            return false; 
        }
        $proto = self::parse($phone);
        if(!$proto) {
            return false;
        }
        $phone = self::$phonenumber->getNationalSignificantNumber($proto);
        $key = self::API_KEY;
        $msg = urlencode("{$msg}");
        $msg = "apikey={$key}&text={$msg}&mobile={$phone}";
        return self::$yunpian->send($msg); 
    }
}
