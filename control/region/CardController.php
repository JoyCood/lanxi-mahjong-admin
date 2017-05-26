<?php
require(DOC_ROOT. '/control/BaseController.php');

class CardController extends BaseController {
	//代理商给其它玩家充房卡
	public function rechargeAction() {
		if($this->request->isGet()) {
			$this->rechargeForm();
		} else if($this->request->isPost()) {
			$this->recharge();
		}
	}

	protected function rechargeForm() {
		$this->render('card/recharge.html', array(
			'options' => require(DOC_ROOT. '/conf/card.config.php')
		));
	}

	protected function recharge() {
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
		    $this->error('请输入正整数');
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
		$this->renderJSON((bool)$result);
	}
}
