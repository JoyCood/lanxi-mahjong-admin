<?php 
require(DOC_ROOT. '/control/BaseController.php');

require_once('lib/wxpay/WxPay.Api.php');
require_once('lib/wxpay/WxPay.Data.php');
require_once('lib/wxpay/WxPay.Exception.php');
require_once('lib/wxpay/WxPay.Notify.php');

require_once('lib/alipay/AopSdk.php');

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
                'errmsg'  => '找不到玩信息'
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
            'Paytype'   => $MoneyInpour::WEIXIN_APP,
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
                'errmsg'  => '暂未开通支付功能，请联系客服'
            ));
        }

        $paramters = array(
            'appid'     => Config::get('core', 'wx.app.id'),
            'noncestr'  => $nonceStr,
            'package'   => 'Sign=WXPay',
            'partnerid' => Config::get('core', 'wx.mch.id'),
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

        if($xmlarr['result_code'] == 'SUCCESS' OR $xmlarr['return _code']=='SUCCESS')
        {
            $wxPayDataBase = new WxPayResults();
            $wxPayDataBase->FromArray($xmlarr);
            $sign = $wxPayDataBase->SetSign();

            if($sign == $xmlarr['sign'])
            {
                echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                unset($xmlarr['sign'], $xmlarr['mch_id'], $xmlarr['openid'], $xmlarr['appid']);
		        $MoneyInpour = Admin::model('money.inpour');
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
				$order = $MoneyInpour->findAndModify($filters, $update, null, $options);
				if(isset($order['Result']) && $order['Result']==$MoneyInpour::DELIVER) {
					$filters = array('_id'=>$order['Userid']);
					$update  = array('$inc' => array('RoomCard' => $order['Quantity']));
					$options = array('new' => true);
					$user    = Admin::model('user.main')->findAndModify($filters, $update); //发货

					$status = $user===NULL? ($MoneyInpour::FAILURE) : ($MoneyInpour::SUCCESS); 
					$filters = array(
						'Transid' => $xmlarr['out_trade_no'], 
						'Userid'  => $order['Userid'],
						'Result'  => $MoneyInpour::DELIVER
					);
					$update = array('Result' => $status);
					$MoneyInpour->update($filters, $update); //更新交易结果

                    //给代理商返现
                    if(isset($order['Rebate']) && $order['Rebate']>0 && isset($order['Parent']) && $order['Parent'] != '') {
                        $filters = array('Gameid' => $order['Parent']);
                        $update  = array('$inc' => array('Balance' => $order['Rebate']));
                        Admin::model('trader.main')->findAndModify($filters, $update);    
                    }

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

	//支付宝APP支付
	public function alipayAction() {
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
        $card = Config::get('card', $cardId);
        if(!$card) {
            $this->responseJSON(array(
                'errcode' => 10001,
                'errmsg'  => '商品不存在'
            ));
        }
        $filter = array('_id' => $userId);
        $user = Admin::model()->findOne($filter);
        if(!$user) {
            $this->responseJSON(array(
                'errcode' => 10002,
                'errmsg'  => '找不到玩家信息'
            ));
        }
        if(Config::BIND_TRADER_ENABLE) {
            $rate   = Config::get('core', 'lx.trader.rate');
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
            'Paytype'   => $MoneyInpour::ALIPAY_APP,
            'Clientip'  => Admin::getRemoteIP(),
            'Parent'    => $user['Build'],
            'Ctime'     => time(),
            'Lv'        => 0,
            'Rebate'    => $rebate,
            'NotifyRes' => array(),
        );
        $MoneyInpour->insert($doc);

        $aop  = new AopClient();	
		$core = Config::getOptions('core');

		$aop->gatewayUrl         = $core['alipay.gateway.url'];
		$aop->appId              = $core['alipay.app.id'];
		$aop->format             = $core['alipay.format'];
		$aop->postCharset        = $core['alipay.charset'];
		$aop->signType           = $core['alipay.sign.type'];
		$aop->rsaPrivateKey      = $core['alipay.private.key'];
		$aop->alipayrsaPublicKey = $core['alipay.public.key'];

		$request = new AlipayTradeAppPayRequest();
		$request->setNotifyUrl($core['alipay.notify.url']);
		$bizcontent = array(
		    'body' => $card['Title'],
			'subject' => $card['Title'],
			'out_trade_no' => $transId,
			'timeout_express' => $core['alipay.timeout.express'],
			'total_amount' => $card['Money'],
			'product_code' => 'QUICK_MSECURITY_PAY'
		);
		$request->setBizContent(json_encode($bizcontent));
		$response = $aop->sdkExecute($request);
		echo htmlspecialchars($response);
	}

    public funciton alipayNotifyAction() {
        $aop  = new AopClient();        
        $core = Config::getOptions('core');
        $aop->alipayrsaPublicKey = $core['alipay.public.key'];
        $result = $aop->rsaCheckV1($_POST, $core['alipay.public.key'], $core['alipay.sign.typ']);

        if($result) {
            if($_POST['trade_status'] == 'TRADE_SUCCESS') {
               $out_trade_no = $_POST['out_trade_no'];
               $MoneyInpour = Admin::model('money.inpour');
               $filters = array(
                   'Transid' => $out_trade_no,
                   'Result'  => $MoneyInpour::PROCESSING
               );
               $update = array('$set' => array(
                   'Result' => $MoneyInpour::DELIVER,
                   'notify_data' => $_POST 
               ));
               $options = array('new') => true);
               $order = $MoneyInpour->findAndModify($filters, $update, null, $options);
               if(isset($order['Result']) && $order['Result'] == $MoneyInpour::DELIVER) {
                   $filters = array('_id' => $order['Userid']);
                   $update  = array('$inc' => array('RoomCard' => $order['Quantity']));
                   $options = array('new' => true);     
                   $user    = Admin::model('user.main')->findAndModify($filters, $update);
                   $status = $user===NULL? ($MoneyInpour::FAILURE) : ($MoneyInpour::SUCCESS);
                   $filters = array(
                       'Transid' => $out_trade_no,
                       'Userid'  => $order['Userid'],
                       'Result'  => $MoneyInpour::DELIVER
                   );
                   $update = array('Result' => $status);
                   $MoneyInpour->update($filters, $update);

                   if(isset($order['Rebate']) && $order['Rebate']>0 && isset($order['Parent'] && $order['Parent'] != '')) {
                       $filters = array('Gameid' => $order['Parent']);
                       $update  = array('$inc' => array('Balance' => $order['Rebate'])); 
                       Admin::model('trader.main')->findAndModify($filters, $update);
                   }

                   $sign    = Config::GAME_SERVER_SIGN;
                   $time    = time();
                   $userid  = (string)$order['Userid'];
                   $transid = (string)$order['Transid'];
                   $kind    = intval($MoneyInpour::GOODS_TYPE_ROOMCARD);
                   $count   = intval($order['Quantity']);
                   $curcount= intval($user['RoomCard']);

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
               echo 'success';
            }      
        }
    }

    //苹果支付发货
    public function IAPNotifyAction() {
        $userId    = trim($this->request->post('userId'));     
        $nonceStr  = trim($this->request->post('nonceStr'));
        $timestamp = trim($this->request->post('timestamp'));
		$token     = trim($this->request->post('token'));
        $receipt   = trim($this->request->post('receipt'));

		$key  = Config::CLIENT_KEY; 
        $sign = md5($receipt);
        $hash = md5("{$key}{$userId}{$nonceStr}{$sign}{$timestamp}");
        
        if($hash != $token) {
            $response = array(
                'errcode' => 10000,
                'errmsg'  => '非法请求'
            );
            $this->responseJSON($response);
        }
        $sandbox = 'https://sandbox.itunes.apple.com/verifyReceipt';
        $release = 'https://buy.itunes.apple.com/verifyReceipt';
        $URL = DEBUG ? $sandbox : $release;
        $receiptData = json_encode(array('receipt-data' => $receipt)); 

        $response = Helper::curl($URL, $receiptData, 'POST', 30);

        $data = json_decode($response, true);
        
        //来自沙盒的支付凭证
        if(isset($data['status']) && $data['status']==21007) {
            $response = Helper::curl($sandbox, $receiptData, 'POST', 30); 
            $data = json_decode($response, true);
        } //来自生产环境的支付凭证
        else if(isset($data['status']) && $data['status']==21008) {
            $response = Helper::curl($release, $receiptData, 'POST', 30); 
            $data = json_decode($response, true);
        }
	    
	    if(!isset($data['status']) || $data['status'] != 0) {	
            $response = array(
                'errcode' => isset($data['status']) ? $data['status'] : 10001,
                'errmsg'  => '验证超时或支付凭证无效'
            );
            $this->responseJSON($response);
        }

		$order  = $data['receipt']['in_app'][0];
		$cardId = $order['product_id']; 
		$card   = Config::get('card', $cardId);
		if(!$card) {
			$response = array(
				'errcode' => 10002,
				'errmsg'  => '商品不存在'
			);
			$this->responseJSON($response);
		} 
        
		$MoneyInpour = Admin::model('money.inpour');
        $filters = array(
            'Transid' => $order['transaction_id'],
            'Result'  => $MoneyInpour::SUCCESS
        );
        $mi = $MoneyInpour->findOne($filters);
        if($mi) {
            $response = array(
                'errcode' => 10003,
                'errmsg'  => '此订单已发货，请勿重复下单'
            ); 

            $this->responseJSON($response);
        }

		$User = Admin::model('user.main');
		$filters  = array('_id' => $userId);
		$update   = array('$inc' => array('RoomCard' => $card['CardNum']));         
		$user = $User->findAndModify($filters, $update);

		if(!$user) {
			$response = array(
				'errcode' => 10004,
				'errmsg'  => '找不到玩家信息'
			);
			$this->responseJSON($response);
		}

		if(Config::BIND_TRADER_ENABLE) {
			$rate = Config::get('core', 'lx.trader.rate');
			$rebate = $card['Money'] * $rate;
		} else {
			$rebate = 0;
		}
		$sign     = Config::GAME_SERVER_SIGN;
        $time     = time();
        $userid   = (string)$userId;
		$transid  = (string)$order['transaction_id'];
		$kind     = intval($MoneyInpour::GOODS_TYPE_ROOMCARD);
        $count    = intval($card['CardNum']);
        $curcount = intval($user['RoomCard']); 

        $key = md5("{$sign}{$time}{$userid}{$transid}{$kind}{$count}{$curcount}");
        $data = array(
            'transid'   => $transid,
            'userid'    => $userid,
            'kind'      => $kind,
            'count'     => $count,
            'curcount'  => $curcount,
            'timestamp' => $time,
            'key'       => $key
        );
        $host = DEBUG ? (Config::DEV_GAME_SERVER_HOST): (Config::GAME_SERVER_HOST);  
        Helper::curl($host, json_encode($data), 'POST');
        
		$doc = array(
			'Transid'   => $order['transaction_id'],
			'Buyer'     => $userId,
			'Userid'    => $userId,
			'Itemid'    => $cardId,
			'Amount'    => $order['quantity'], //商品数量
			'Quantity'  => $card['CardNum'], //获得的房卡数
			'Money'     => $card['Money'],
			'Transtime' => time(),
			'Result'    => $MoneyInpour::SUCCESS,
			'Currency'  => 'CNY',
			'Paytype'   => $MoneyInpour::IAP,
			'Clientip'  => Admin::getRemoteIP(),
			'Parent'    => $user['Build'], //上级代理
			'Ctime'     => time(),
			'Lv'        => 0,
			'Rebate'    => $rebate, 
			'NotifyRes' => $data,
		);
        $MoneyInpour->insert($doc);
		$response = array('errcode' => 0);
		$this->responseJSON($response);
    }
}
