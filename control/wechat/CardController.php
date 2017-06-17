<?php 
require(DOC_ROOT. '/control/WechatController.php');
require_once('lib/wxpay/WxPay.Api.php');
require_once('lib/wxpay/WxPay.Data.php');
require_once('lib/wxpay/WxPay.Exception.php');
require_once('lib/wxpay/WxPay.Notify.php');

class CardController extends WechatController {

    public function wxPayAction() {
        if(!isset($_SESSION[self::MP_SESSION_OPENID]) || $_SESSION[self::MP_SESSION_OPENID]=='') {
            $this->rechargeForm();
        }
         
        $userId = '10000';
		$buyer  = $userId;
        $cardId  = '1';

        $card = Config::get('card', $cardId);
        if(!$card) {
            $this->error('商品不存在');
        }
        $filter = array('_id' => $userId);
        $user = Admin::model('user.main')->findOne($filter);
        if(!$user) {
             $this->error('用户不存在');
        }

        $MoneyInpour = Admin::model('money.inpour');
        $transId = date('YmdHis'). Helper::mkrand();
        $doc = array(
            'Transid'   => $transId,
			'Buyer'     => $buyer,
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
        $input->SetAppid(Config::get('core', 'wx.mp.id'));
        $input->SetMch_id(Config::get('core', 'wx.mch.id'));
        $input->SetOpenid($_SESSION[self::MP_SESSION_OPENID]);
        $input->SetBody($card['Title']); //Bong 这是个炸弹，不要改这行
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

        $paramters = array(
            'appid'     => Config::get('core', 'wx.mp.id'),
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
        $this->renderJSON($result);
    }

	//绑定代理商
	public function bindTraderAction() {
		//未开启绑定代理商的功能，返回成功
		if(!Config::BIND_TRADER_ENABLE) {
		    $this->renderJSON(true);
		}

		if(!isset($_SESSION[self::MP_SESSION_OPENID]) || $_SESSION[self::MP_SESSION_OPENID]=='') {
		    $this->rechargeForm();
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
		$result = $user->update($filters, $update);
		if($result['nModified']>0) {
		    $this->renderJSON(true);
		} else {
		    $this->error('系统出错，请稍后重试');
		}
	} 

	public function rechargeAction() {
		if($this->request->isGet()) {
		    $this->rechargeForm();
		} else if($this->request->isPost()) {
		    $this->recharge();
		}
	}

	protected function rechargeForm() {
		$url = Helper::requestURI();
		if(1) {
			$userinfo = $this->login($url);
			if(isset($userinfo['unionid'])) {
			    $User     = Admin::model('user.main');
			    $filter   = array('Wechat_unionid'=>$userinfo['unionid']);
			    $userinfo = $User->findOne($filter);
				$invite   = $user['Build'] == '' && $bindTrader == TRUE;
			}
		} else {
			// 仅测试
			$userinfo = array();
			$invite   = true;
		}

		$this->render('card/recharge.html', array(
			'userinfo'   => $userinfo,
			'bindTrader' => Config::BIND_TRADER_ENABLE,
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
				'NickName' => $data['Nickname'],
				'Phone'    => $data['Phone'],
				'RoomCard' => number_format($data['RoomCard'], 0),
			);
		} else {
			return null;
		}
	}
}

