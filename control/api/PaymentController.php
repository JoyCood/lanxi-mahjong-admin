<?php
require(DOC_ROOT. '/control/BaseController.php');

require_once('lib/wxpay/WxPay.Api.php');
require_once('lib/wxpay/WxPay.Data.php');
require_once('lib/wxpay/WxPay.Exception.php');
require_once('lib/wxpay/WxPay.Notify.php');

class PaymentController extends BaseController {
    //下单
    public function wxPayAction() {
        $userId    = $this->request->post('userId');         
        $cardId    = $this->request->post('cardId');
        $card      = Config::get('card', $cardId);

        if(!$card) {
            $this->responseJSON(array(
                'code' => 10001,
                'msg'  => '商品不存在',
            ));
        }

        $filter    = array('_id' => $userId);
        $user = Admin::model('user.main')->findOne($filter);
        if(!$user) {
            $this->responseJSON(array(
                'code' => 10000, 
                'msg'  => '用户不存在'
            ));
        }
        $MoneyInpour = Admin::model('money.inpour');
        $transId = date('YmdHis'). Helper::mkrand();
        $doc = array(
            'Transid'   => $transId,
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
            'Parent'    => $user['Build'],
            'Ctime'     => time(),
            'Lv'        => 0,
            'Rebate'    => 0, //给上级代理商的返点
            'NotifyRes' => array(),
        ); 
        $MoneyInpour->insert($doc);

        $nonceStr = md5(Helper::mkrand());
        $input = new WxPayUnifiedOrder();
        $input->SetBody($card['Title']);
        $input->SetNonce_str($nonceStr);
        $input->SetOut_trade_no($transId);
        $input->SetTotal_fee($card['Money'] * 100);
        $input->SetTrade_type('APP');
        $prepay = WxPayApi::unifiedOrder($input);

        if(!isset($prepay['prepay_id'])) {
            $this->responseJSON(array(
                'code' => 10001, 
                'msg'  => '支付出错，请稍后重试'
            ));
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
				$update = array(
				    'Result' => $MoneyInpour::DELIVER,
					'notify_data' => $xmlarr
				);
				$options = array('new' => true);
				//状态更新为发货中，用于锁定
				$order = Admin::model('money.inpour')->findAndModify($filters, $update, null, $options);
				if(isset($order['Result']) && $order['Result']==$MoneyInpour::DELIVER) {
					$filters = array('Gameid'=>$order['Userid']);
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
					$sign     = Config::getOptions('game-server-sign');
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
					Helper::curl(Config::getOptions('game-server-host'), json_encode($data), 'POST');
				}
            }
        }
        else
        {
            echo '<xml><return_code><![CDATA[FAILURE]]></return_code><return_msg><![CDATA[ERROR]]></return_msg></xml>';
        }
    }
}

/*
SIGN = "XG0e2Ye/KAUJRXaMNnJ5UH1haBvh2FXOoAggE6f2Utw"

Key = Md5(SIGN+Timestamp+Useridz+Kind+Count)

发货Post 过来的数据(json格式)：

Key string `json:"key"` // 秘钥
Userid string `json:"userid"` // 玩家id
Kind uint32 `json:"kind"` // 商品类型1:房卡
Count uint32 `json:"count"` // 商品数量
Timestamp uint32 `json:"timestamp"` // 发货时间戳
*/