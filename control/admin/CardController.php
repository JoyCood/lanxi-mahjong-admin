<?php
require(DOC_ROOT. '/control/BaseController.php');

class CardController extends BaseController {
	//客服给代理商充房卡
	public function rechargeAction() {
		$target = trim($this->request->post('target'));
		$quantity = intval($this->request->post('quantity'));

		if(!$target) {
		    $this->error('请输入代理商游戏ID');
		}

		if($quantity<0) {
		    $this->error('数量不能小于0');
		}
	    	
		$User = Admin::model('user.main');
		$filters = array('_id' => $target);
		$update = array('$inc'=>array('RoomCard' => $quantity));
		$options = array('new' => true);
		$result = $user->findAndModify($filters, $update, null, $options);
		if(!$result) {
		    $this->error('充值失败，请确认输入的玩家是否正确');
		}
		$this->renderJSON((bool)$result);
	} 
}
