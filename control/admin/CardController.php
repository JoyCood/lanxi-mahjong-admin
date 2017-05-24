<?php
require(DOC_ROOT. '/control/AdminBaseController.php');

class CardController extends AdminBaseController {
	protected function init() {
		$this->addViewData('MENU_ITEM', 'card');
	}

	//客服给代理商充房卡
	public function rechargeAction() {
		if($this->request->isGet()) {
			$this->rechargeForm();
		} else if($this->request->isPost()) {
			$this->recharge();
		}
	}

	protected function rechargeForm() {
		$this->render('card/recharge-form.html', array(

		));
	}

	protected function recharge() {
		$target   = intval($this->request->post('target'));
		$quantity = intval($this->request->post('quantity'));

		if(!$target) {
		    $this->error('请输入代理商游戏ID');
		}
		if($quantity<0) {
		    $this->error('数量不能小于0');
		}
	    	
		$User    = Admin::model('user.main');
		$filters = array('_id'  => strval($target));
		$update  = array('$inc' => array('RoomCard' => $quantity));
		$options = array('new'  => true);
		$pre     = $User->findOne($filters);
		$result  = $User->findAndModify($filters, $update, null, $options);
		if(!$result) {
		    $this->error('充值失败，请确认输入的玩家是否正确');
		}

		$data = $User->findOne($filters);
		if($data) {
			$this->logData('recharge', $pre? $pre['RoomCard']: null, $data['RoomCard'], $data['_id']);
		}
		$this->renderJSON(array(
			'result' => (bool)$result,
			'data'   => array(
				'Nickname' => $data? $data['Nickname']: '',
				'RoomCard' => number_format($data? $data['RoomCard']: 0, 0)
			)
		));
	} 
}
