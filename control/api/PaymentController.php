<?php
require(DOC_ROOT. '/control/BaseController.php');

require('lib/wxpay/WxPay.Api.php');
require('lib/wxpay/WxPay.Data.php');
require('lib/wxpay/WxPay.Exception.php');
require('lib/wxpay/WxPay.Notify.php');

class PaymentController extends BaseController {
    //下单
    public function placeOrderAction() {
    
    }

    //支付回调
    public function wxPayNotifyAction() {
        $xmlstr = file_get_contents("php://input");
        $xmlstr = str_replace('<![CDATA[', '', $xmlstr);
        $xmlstr = str_replace(']]>', '', $xmlstr);
        
        $xmlobj = simplexml_load_string($xmlstr);
        $xmlarr = get_object_vars($xmlobj);

        if($xmlarr['result_code'] == 'SUCCESS' OR $xmlarr['return _code'])
        {
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            
            $wxPayDataBase = new WxPayResults();
            $wxPayDataBase->FromArray($xmlarr);
            $sign = $wxPayDataBase->SetSign();

            if($sign == $xmlarr['sign'])
            {
                unset($xmlarr['sign'], $xmlarr['mch_id'], $xmlarr['openid'], $xmlarr['appid']);
                //Admin::model('course.order')->updateOrderStatu($xmlarr['out_trade_no'], 1, 'weixin', $xmlarr);
            }
        }
        else
        {
            echo '<xml><return_code><![CDATA[FAILURE]]></return_code><return_msg><![CDATA[ERROR]]></return_msg></xml>';
        }
    }
}
