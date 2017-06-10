<?php 
require(DOC_ROOT. '/control/WechatController.php');
require_once('lib/wxpay/WxPay.Api.php');
require_once('lib/wxpay/WxPay.Data.php');
require_once('lib/wxpay/WxPay.Exception.php');
require_once('lib/wxpay/WxPay.Notify.php');

class CardController extends WechatController {

    public function wxPayAction() {
        $userId = '16004';
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
        $input->SetBody('游泳培训课程支付测试'); //Bong 这是个炸弹，不要改这行
        $input->SetNonce_str($nonceStr);
        $input->SetOut_trade_no($transId);
        $input->SetTotal_fee($card['Money'] * 100);
        $input->SetTrade_type('APP');
        $prepay = WxPayApi::unifiedOrder($input);
        if(!isset($prepay['prepay_id'])) {
            $this->error('支付出错，请稍后重试');
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

	public function rechargeAction() {
		if($this->request->isGet()) {
		    $this->rechargeForm();
		} else if($this->request->isPost()) {
		    $this->recharge();
		}
	}

	protected function rechargeForm() {
		$url = Helper::requestURI();
		$userinfo = $this->login($url);
		if(isset($userinfo['unionid'])) {
		    $User = Admin::model('user.main');
		    $filter = array('Wechat_unionid'=>$userinfo['unionid']);
		    $userinfo = $User->findOne($filter);
		}
		$this->render('card/recharge.html', array(
			'userinfo' => $userinfo,
		    'options'  => require(DOC_ROOT. '/conf/card.config.php')
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

