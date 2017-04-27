<?php
require(DOC_ROOT. '/control/BaseController.php');

require_once('lib/wxpay/WxPay.Api.php');
require_once('lib/wxpay/WxPay.Data.php');
require_once('lib/wxpay/WxPay.Exception.php');
require_once('lib/wxpay/WxPay.Notify.php');

class PaymentController extends BaseController {
    //下单
    public function placeOrderAction() {
        $userId    = '11006';//$this->request->post('userId');         
        $goodsId   = '1';//$this->request->post('goodsId');
        $secret    = '';//$this->request->post('secret');
        $timestamp = $this->request->post('timestamp');
        $goods     = Config::get('goods', $goodsId);

        $filter    = array('_id' => $userId);
        $user = Admin::model('user.main')->findOne($filter);
        if(!$user) {
            $this->renderJSON(array(
                'code' => 10000, 
                'msg'  => '用户不存在'
            ));
            exit();
        }

        $transId = date('YmdHis'). Helper::mkrand();
        $doc = array(
            'Transid'   => $transId,
            'Userid'    => $userId,
            'Amount'    => 1,
            'Quantity'  => $goods['quantity'],
            'Money'     => $goods['price'] * 100,
            'Transtime' => time(),
            'Result'    => 1,
            'Currency'  => 'CNY',
            'Paytype'   => 1,
            'Clientip'  => Admin::getRemoteIP(),
            'Parent'    => $user['Build'],
            'Ctime'     => time(),
            'Lv'        => 0,
            'Rebate'    => 0,
        ); 
        Admin::model('money.inpour')->insert($doc);

        $nonceStr = md5(Helper::mkrand());
        $input = new WxPayUnifiedOrder();
        $input->SetBody($goods['title']);
        $input->SetNonce_str($nonceStr);
        $input->SetOut_trade_no($transId);
        $input->SetTotal_fee($goods['price'] * 100);
        $input->SetTrade_type('APP');
        $prepay = WxPayApi::unifiedOrder($input);

        if(!isset($prepay['prepay_id'])) {
            $this->renderJSON(array(
                'code' => 10001, 
                'msg'  => '支付出错，请稍后重试'
            ));
            exit();
        }

        $paramters = array(
            'appid'     => Config::get('payment', 'wx.app.id'),
            'noncestr'  => $nonceStr,
            'package'   => 'Sign=WXPay',
            'partnerid' => Config::get('payment', 'wx.mch.id'),
            'prepayid'  => $prepay['prepay_id'],
            'timestamp' => time(),
        );

        $wxPayDataBase = new WxPayResults();
        $wxPayDataBase->FromArray($paramters);
        $wxPayDataBase->SetSign();
        $result = $wxPayDataBase->getValues();
        $result['out_trade_no'] = $transId;
        $data = array(
            'code' => 0,
            'data' => $result
        );
        $this->renderJSON($data);
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
