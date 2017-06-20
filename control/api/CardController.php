<?php 
require(DOC_ROOT. '/control/BaseController.php');

require_once('lib/wxpay/WxPay.Api.php');
require_once('lib/wxpay/WxPay.Data.php');
require_once('lib/wxpay/WxPay.Exception.php');
require_once('lib/wxpay/WxPay.Notify.php');

class CardController extends BaseController {
    //商品列表
	public function listAction() {
        $card = Config::getOptions('card');
        //$encryped = Helper::encodeParams($goods); 
        //$decryped = Helper::decodeParams($encryped);
        $this->renderJSON($card);  
    }

    //下单
    public function wxPayAction() {
        $userId    = trim($this->request->post('userId'));         
        $cardId    = trim($this->request->post('cardId'));
        $nonceStr  = trim($this->request->post('nonceStr'));
        $timestamp = trim($this->request->post('timestamp'));
        $sign      = trim($this->request->post('sign'));

        $key = Config::CLIENT_KEY;
        
        $hash = md5("{$userId}{$key}{$cardId}{$nonceStr}{$timestamp}");
        if($hash != $sign) {
            $this->responseJSON(array(
                'errcode' => 10000,
                'errmsg'  => '非法请求'
            ));
        }
        $card   = Config::get('card', $cardId);

        if(!$card) {
            $this->responseJSON(array(
                'errcode' => 10001,
                'errmsg'  => '商品不存在',
            ));
        }

        $filter    = array('_id' => $userId);
        $user = Admin::model('user.main')->findOne($filter);
        if(!$user) {
            $this->responseJSON(array(
                'errcode' => 10002, 
                'errmsg'  => '玩家不存在'
            ));
        }
        if(Config::BIND_TRADER_ENABLE) {
            $rate = Config::get('core', 'lx.trader.rate');
            $rebate = $card['Money'] * $rate;
        } else {
            $rebate = 0;
        }

        $MoneyInpour = Admin::model('money.inpour');
        $transId = date('YmdHis'). Helper::mkrand();
        $doc = array(
            'Transid'   => $transId,
            'Buyer'     => $userId,
            'Userid'    => $userId,
            'Itemid'    => $cardId,
            'Amount'    => 1,
            'Quantity'  => $card['CardNum'],
            'Money'     => $card['Money'],
            'Transtime' => time(),
            'Result'    => $MoneyInpour::PROCESSING,
            'Currency'  => 'CNY',
            'Paytype'   => $MoneyInpour::WEIXIN,
            'Clientip'  => Admin::getRemoteIP(),
            'Parent'    => $user['Build'], //上级代理
            'Ctime'     => time(),
            'Lv'        => 0,
            'Rebate'    => $rebate, //给上级代理商的返点
            'NotifyRes' => array(), //微信回调结果
        ); 
        $MoneyInpour->insert($doc);

        $nonceStr = md5(Helper::mkrand());
        $input = new WxPayUnifiedOrder();
        $input->SetAppid(Config::get('core', 'wx.app.id'));
        $input->SetMch_id(Config::get('core', 'wx.mch.id'));
        $input->SetBody($card['Title']);
        $input->SetNonce_str($nonceStr);
        $input->SetOut_trade_no($transId);
        $input->SetTotal_fee($card['Money'] * 100);
        $input->SetTrade_type('APP');
        $input->SetNotify_url(Config::get('core', 'wx.notify.url'));
        $prepay = WxPayApi::unifiedOrder($input);
        if(!isset($prepay['prepay_id'])) {
            $this->log->debug(json_encode($prepay));
            $this->responseJSON(array(
                'errcode' => 10003, 
                'errmsg'  => '支付出错，请稍后重试'
            ));
        }

        $paramters = array(
            'appid'     => $appid, //Config::get('core', 'wx.app.id'),
            'noncestr'  => $nonceStr,
            'package'   => 'Sign=WXPay',
            'partnerid' => $mchid, //Config::get('core', 'wx.mch.id'),
            'prepayid'  => $prepay['prepay_id'],
            'timestamp' => time(),
        );

        $wxPayDataBase = new WxPayResults();
        $wxPayDataBase->FromArray($paramters);
        $wxPayDataBase->SetSign();
        $result = $wxPayDataBase->getValues();
        $result['out_trade_no'] = $transId;
        $data = array(
            'errcode' => 0,
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
        $this->log->debug(json_encode($xmlarr));

        if($xmlarr['result_code'] == 'SUCCESS' OR $xmlarr['return _code'])
        {
            $wxPayDataBase = new WxPayResults();
            $wxPayDataBase->FromArray($xmlarr);
            $sign = $wxPayDataBase->SetSign();

            if($sign == $xmlarr['sign'])
            {
                echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                unset($xmlarr['sign'], $xmlarr['mch_id'], $xmlarr['openid'], $xmlarr['appid']);
                $filters = array(
					'Transid' => $xmlarr['out_trade_no'],
					'Result'  => $MoneyInpour::PROCESSING
				);
				$update = array( '$set' => array(
				    'Result' => $MoneyInpour::DELIVER,
					'notify_data' => $xmlarr
				));
				$options = array('new' => true);
				//状态更新为发货中，用于锁定
				$order = Admin::model('money.inpour')->findAndModify($filters, $update, null, $options);
				if(isset($order['Result']) && $order['Result']==$MoneyInpour::DELIVER) {
					$filters = array('_id'=>$order['Userid']);
					$update  = array('$inc' => array('RoomCard' => $order['Quantity']));
					$options = array('new' => true);
					$user    = Admin::model('user.main')->findAndModify($filters, $update); //发货
                    
					$status = $user===NULL? $MoneyInpour::FAILURE : $MoneyInpour::SUCCESS; 
					$update = array('Result' => $status);
					$filters = array(
						'Transid'=>$xmlarr['out_trade_no'], 
						'Userid'=>$order['Userid'],
						'Result'=>$MoneyInpour::DELIVER
					);
					$MoneyInpour->update($filters, $update); //更新交易结果

					//通知游戏服务器发货结果
					$sign     = Config::GAME_SERVER_SIGN;
					$time     = time();
					$userid   = (string)$order['Userid'];
					$transid  = (string)$xmlarr['out_trade_no'];
					$kind     = intval($MoneyInpour::GOODS_TYPE_ROOMCARD);
					$count    = intval($order['Quantity']);
					$curcount = intval($user['RoomCard']);

					$key = md5($sign.$time.$userid.$transid.$kind.$count.$curcount);
					
					$data = array(
					    'transid'   => $transid,
						'userid'    => $userid,
						'kind'      => $kind,
						'count'     => $count,
						'curcount'  => $curcount,
						'timestamp' => $time,
						'key'       => $key
					);
					Helper::curl(Config::GAME_SERVER_HOST, json_encode($data), 'POST');
				}
            }
        }
        else
        {
            echo '<xml><return_code><![CDATA[FAILURE]]></return_code><return_msg><![CDATA[ERROR]]></return_msg></xml>';
        }
    }
}
