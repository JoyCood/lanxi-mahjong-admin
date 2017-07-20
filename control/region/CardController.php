<?php
require(DOC_ROOT. '/control/WechatController.php');
require_once('lib/wxpay/WxPay.Api.php');
require_once('lib/wxpay/WxPay.Data.php');
require_once('lib/wxpay/WxPay.Exception.php');
require_once('lib/wxpay/WxPay.Notify.php');

require_once('lib/alipay/AopSdk.php');

class CardController extends WechatController {

    public function wxPayAction() {
        if(!isset($_SESSION[self::MP_SESSION_OPENID]) || $_SESSION[self::MP_SESSION_OPENID]=='') {
			$this->error('please login');
            $baseURL = Config::get('core', 'lx.base.url');
            header("Location:{$baseURL}/region/wechatCR");
        }
		$this->error('暂未开通支付功能.');
        $userId = trim($this->request->post('target')); //充值对像
        $cardId = trim($this->request->post('option')); //充值金额
        
        $card = Config::get('card', $cardId);
        if(!$card) {
            $this->error('商品不存在');
        }
        $filter = array('_id' => $userId);
        $user = Admin::model('user.main')->findOne($filter);
        if(!$user) {
             $this->error('玩家不存在，请重新输入');
        }
        $MoneyInpour = Admin::model('money.inpour');
        $transId = date('YmdHis'). Helper::mkrand();
        $doc = array(
            'Transid'   => $transId,
			'Buyer'     => $_SESSION[self::MP_SESSION_UNIONID],
            'Userid'    => $userId,
            'Itemid'    => $cardId,
            'Amount'    => 1,
            'Quantity'  => $card['CardNum'],
            'Money'     => $card['Money'],
            'Transtime' => time(),
            'Result'    => $MoneyInpour::PROCESSING,
            'Currency'  => 'CNY',
            'Paytype'   => $MoneyInpour::WEIXIN_WAP,
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
        $input->SetAppid(Config::get('core', 'wx.mp.id'));
        $input->SetMch_id(Config::get('core', 'wx.mp.mch.id'));
        $input->SetOpenid($_SESSION[self::MP_SESSION_OPENID]);
        $input->SetBody($card['Title']); 
        $input->SetNonce_str($nonceStr);
        $input->SetOut_trade_no($transId);
        $input->SetTotal_fee($card['Money'] * 100);
        $input->SetTrade_type('JSAPI');
        $input->SetNotify_url(Config::get('core', 'wx.notify.url'));
        
        $prepay = WxPayApi::unifiedOrder($input);

        if(!isset($prepay['prepay_id'])) {
            $this->log->debug(json_encode($prepay));
            $this->error('支付出错，请稍后重试');
        }

        $time = time();
        $wxPayJsApiPay = new WxPayJsApiPay();
		$wxPayJsApiPay->SetAppid(Config::get('core', 'wx.mp.id'));
        $wxPayJsApiPay->SetTimeStamp("{$time}");
		$wxPayJsApiPay->SetNonceStr($nonceStr);
		$wxPayJsApiPay->SetPackage("prepay_id={$prepay['prepay_id']}");
		$wxPayJsApiPay->SetSignType('MD5');
		$wxPayJsApiPay->SetPaySign($wxPayJsApiPay->MakeSign());
        $result = $wxPayJsApiPay->getValues();
        $this->renderJSON($result);
    }

	//支付宝wap支付
	public function alipayAction() {
        $cardId = trim($this->request->post('option'));
        $card = Config::get('card', $cardId);
        if(!$card) {
            $this->error('您选择的房卡不存在');
        }
        //$userId = '10000';
        $filter = array('_id' => $_SESSION[Config::SESSION_UID]);
        $user = Admin::model('user.main')->findOne($filter);
        if(!$user) {
            $this->error('请重新登录或到微信公众号联系客服');
        }
        $MoneyInpour = Admin::model('money.inpour');
        $transId = date('YmdHis'). Helper::mkrand();
        $doc = array(
            'Transid'   => $transId,
            'Buyer'     => $_SESSION[Config::SESSION_UID],
            'Userid'    => $_SESSION[Config::SESSION_UID],
            'Itemid'    => $cardId,
            'Amount'    => 1,
            'Quantity'  => $card['CardNum'],
            'Money'     => $card['Money'],
            'Transtime' => time(),
            'Result'    => $MoneyInpour::PROCESSING,
            'Currency'  => 'CNY',
            'Paytype'   => $MoneyInpour::ALIPAY_WAP,
            'Clientip'  => Admin::getRemoteIP(),
            'Parent'    => $user['Build'],
            'Ctime'     => time(),
            'Lv'        => 0,
            'Rebate'    => 0,
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
		
		$request = new AlipayTradeWapPayRequest();
		$request->setNotifyUrl($core['alipay.notify.url']);
		$request->setReturnUrl($core['alipay.return.url']);
		$bizcontent = array(
		    'body'            => $card['Title'],
			'subject'         => $card['Title'],
			'out_trade_no'    => $transId,
			'timeout_express' => $core['alipay.timeout.express'],
			'total_amount'    => $card['Money'],
			'product_code'    => 'QUICK_WAP_PAY'
		);
		$request->setBizContent(json_encode($bizcontent));
		echo $aop->pageExecute($request);
	}

    public function alipayResultAction() {
		$aop  = new AopClient();
        $core = Config::getOptions('core');	
		$aop->alipayrsaPublicKey = $core['alipay.public.key'];
		$result = $aop->rsaCheckV1($_GET, $core['alipay.public.key'], $core['alipay.sign.type']);
	}

    //从浏览器通过支付宝wap支付批发房卡
    public function alipayCustomeRecargeAction() {
        $filter = array('_id' => $_SESSION[Config::SESSION_UID]);
        $userinfo = Admin::model('user.main')->findOne($filter);
        $this->render('card/alipay-recharge.html', array(
            'options'  => Config::getOptions('card'),
            'userinfo' => $userinfo
        ));
    }

    //支付宝wap支付结果展示
    public function alipayResultAction() {
        $aop  = new AopClient();  
        $core = Config::getOptions('core');
        $aop->alipayrsaPublicKey = $core['alipay.public.key'];
        $result = $aop->rsaCheckV1($_GET, $core['alipay.public.key'], $core['alipay.sign.type']);
        $order = array();
        if($result) { //签名校验正确通过
            $Transid = trim($this->request->get('out_trade_no')); 
            $MoneyInpour = Admin::model('money.inpour');
            $filters = array(
                'Transid' => $Transid,
                'Userid'  => $_SESSION[Config::SESSION_UID],
                'Paytype' => $MoneyInpour::ALIPAY_WAP 
            );
            $order = $MoneyInpour->findOne($filters); //订单存在,但不代表已支付，除非Result字段的值为0时代表支付成功并已发货
        }
        $this->render('card/alipay-result.html', array(
            'order'  => $order,
            'result' => ($result && !empty($order)) //支付结果:true成功，false失败
        ));
    }

	//代理商给玩家充房卡
	public function customRechargeAction() {
		if($this->request->isGet()) {
			$this->setCsrfToken();
			$this->customRechargeForm();
		} else if($this->request->isPost()) {
			$this->checkCsrfToken();
			$this->customRecharge();
		}
	}

    //从公众号批发房卡
    public function wechatCustomeRechargeAction() {
        $url = Helper::requestURI();
        $userinfo = $this->login($url); 
        if(isset($userinfo['unionid'])) {
            $User = Admin::model('user.main');
            $filter = array('Wechat_unionid' => $userinfo['unionid']);        
            $userinfo = $User->findOne($filter);
        }

        $this->render('card/recharge.html', array(
            'options'  => Config::getOptions('card'),
            'userinfo' => $userinfo
        ));
    }

	protected function customRechargeForm() {
		$this->render('card/custom-recharge.html', array(
            'options' => Config::getOptions('card')
		));
	}

	//给玩家充值房卡
	protected function customRecharge() {
		$target   = trim($this->request->post('target'));
	    $quantity = intval($this->request->post('quantity'));

		$User    = Admin::model('user.main');
		$filters = array('_id' => $_SESSION[Config::SESSION_UID]);
		$trader  = $User->findOne($filters);
		if(!$trader) {
		    $this->error('请重新登录');
		}
		if(!$target) {
		    $this->error('请输入玩家游戏ID');
		}
		if($quantity<0) {
		    $this->error('房卡请输入正整数');
		}
		if($trader['RoomCard']<$quantity) {
		    $this->error('房卡不足，请联系客服购买房卡');
		}
		//先给房家充值成功后再扣除代理商的房卡
		$filters = array('_id' => $target);
		$update  = array('$inc'=>array('RoomCard'=>$quantity));
		$result  = $User->findAndModify($filters, $update);
		if($result) { //扣除代理商的对应数量的房卡
			$quantity *= -1;
			$filters = array('_id' => $_SESSION[Config::SESSION_UID]);
			$update = array('$inc'=>array('RoomCard'=>$quantity));
			$options = array('new' => true);
			$User->findAndModify($filters, $update, null, $options);
		}

		$data = $this->getTargetInfo($target);
		$this->renderJSON($data? $data: array());
	}

	public function userAction() {
		$target = intval($this->request->post('target'));
		$data   = $this->getTargetInfo($target);
		if($data) {
			$this->renderJSON($data);
		} else {
			$this->error('未找到用户信息');
		}
	}

	protected function getTargetInfo($target) {
		$User   = Admin::model('user.main');
		$filter = array('_id' => strval($target));
		$data   = $User->findOne($filter);
		if($data) {
			return array(
				'Nickname' => $data['Nickname'],
				//'Phone'    => $data['Phone'],
				'RoomCard' => number_format($data['RoomCard'], 0),
			);
		} else {
			return null;
		}
	}
}
