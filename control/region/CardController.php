<?php
require(DOC_ROOT. '/control/WechatController.php');

class CardController extends WechatController {

    public function wxPayAction() {
        if(!isset($_SESSION[self::MP_SESSION_OPENID]) || $_SESSION[self::MP_SESSION_OPENID]=='') {
            $baseURL = Config::get('core', 'lx.base.url');
            header("Location:{$baseURL}/region/wechatCR");
        }
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
/*
        //开启绑定代理商的时候购买房卡才给代理商提成
        if(Config::BIND_TRADER_ENABLE) {
            $rate   = Config::get('core', 'lx.trader.rate');
            $rebate = $card['Money'] * $rate;
        } else {
            $rebate = 0; 
        }
 */
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
	//代理商给其它玩家充房卡
	public function rechargeAction() {
		if($this->request->isGet()) {
			$this->setCsrfToken();
			$this->rechargeForm();
		} else if($this->request->isPost()) {
			$this->checkCsrfToken();
			$this->recharge();
		}
	}

	protected function rechargeForm() {
		$this->render('card/recharge.html', array(
			'options' => Config::getOptions('card')
		));
	}

	protected function recharge() {
		$target   = intval($this->request->post('target'));
	    $optValue = trim($this->request->post('option'));
		$options  = Config::getOptions('card');
	    $quantity = 0;
	    $money    = 0;

		if(empty($options[$optValue])) {
			$this->error('请选择房卡数量');
		} else {
			$quantity = $options[$optValue]['CardNum'];
			$money    = $options[$optValue]['Money'];
		}

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
		    $this->error('请输入正整数');
		}
		if($trader['RoomCard']<$quantity) {
		    $this->error('房卡不足，请联系客服购买房卡');
		}
		//先给玩家充值成功后再扣除代理商的房卡
		$filters = array('_id' => strval($target));
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
/*
	protected function customRecharge() {
		$target   = intval($this->request->post('target'));
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
		$filters = array('_id' => strval($target));
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
 */
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
