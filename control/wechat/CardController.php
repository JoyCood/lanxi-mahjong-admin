<?php 
require(DOC_ROOT. '/control/WechatController.php');
require_once('lib/wxpay/WxPay.Api.php');
require_once('lib/wxpay/WxPay.Data.php');
require_once('lib/wxpay/WxPay.Exception.php');
require_once('lib/wxpay/WxPay.Notify.php');

class CardController extends WechatController {

    public function wxPayAction() {
        if(!isset($_SESSION[self::MP_SESSION_OPENID]) || $_SESSION[self::MP_SESSION_OPENID]=='') {
            $baseURL = Config::get('core', 'lx.base.url');
            header("Location:{$baseURL}/wechat/recharge");
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

        //开启绑定代理商的时候购买房卡才给代理商提成
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
            'Rebate'    => $rebate, //给上级代理商的返点
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

	//绑定代理商
	public function bindTraderAction() {
		//未开启绑定代理商的功能，返回成功
		if(!Config::BIND_TRADER_ENABLE) {
		    $this->renderJSON(true);
		}

		if(!isset($_SESSION[self::MP_SESSION_OPENID]) || $_SESSION[self::MP_SESSION_OPENID]=='') {
            $baseURL = Config::get('core', 'lx.base.url');
            header("Location:{$baseURL}/wechat/recharge");
		}

		$trader = trim($this->request->post('trader'));
		if(!$trader) {
		    $this->error('请输入邀请码');
		}

		$Trader = Admin::model('trader.main');
		$filters = array('Gameid' => $trader);
		$trader = $Trader->findOne($filters);
		//代理商不存在
		if(!$trader) {
		    $this->error('邀请码错误,请重新输入');
		}

		$User = Admin::model('user.main');
		$filters = array('Wechat_unionid' => $_SESSION[self::MP_SESSION_UNIONID]);
		$user = $User->findOne($filters);
		//非微信登录用户
		if(!$user) {
		    $this->error('请用微信登录游戏后再输入邀请码');
		}

		//不能绑定自己
		if($user['_id']==$trader) {
		    $this->error('邀请码不能是自己的游戏ID');
		}

		//已经绑定过了，直接返回成功
		if(isset($user['Build']) && $user['Build'] != '') {
		    $this->renderJSON(true); 
			exit();
		}
		$update = array(
		    'Build' => $trader['Gameid'],
			'BuildTime' => time()
		);
		$result = $User->update($filters, $update);
		if($result['nModified']>0) {
		    $this->renderJSON(true);
		} else {
		    $this->error('系统出错，请稍后重试');
		}
	} 

	public function rechargeAction() {
		$url      = Helper::requestURI();
		$userinfo = $this->login($url);
		if(isset($userinfo['unionid'])) {
			$User     = Admin::model('user.main');
			$filter   = array('Wechat_unionid'=>$userinfo['unionid']);
			$userinfo = $User->findOne($filter);
			$bindTrader = Config::BIND_TRADER_ENABLE;
			$invite   = $userinfo['Build'] == '' && $bindTrader == TRUE;
		}

		$this->render('card/recharge.html', array(
			'userinfo'   => $userinfo,
		    'options'    => Config::getOptions('card'),
			'invite'     => $invite,
		));
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
				'Phone'    => $data['Phone'],
				'RoomCard' => number_format($data['RoomCard'], 0),
			);
		} else {
			return null;
		}
	}
}

