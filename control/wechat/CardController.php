<?php 
require(DOC_ROOT. '/control/WechatController.php');

class CardController extends WechatController {
	//代理商给其它玩家充房卡
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
			'options' => require(DOC_ROOT. '/conf/card.config.php')
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

