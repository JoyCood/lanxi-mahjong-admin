<?php
require(DOC_ROOT. '/control/BaseController.php');

class MoneyController extends BaseController {
	public function withdrawFormAction() {
		$filters = array('Gameid' => $_SESSION[Config::SESSION_UID]);    
		$trader  = Admin::model('trader.main')->findOne($filters, array(
			'Status' => 1, 
			'Balance'=> 1
		));

		$this->render('/money/withdraw.html', $trader);
	}

	//申请提现
	public function withdrawAction() {
		$money   = (float)$this->request->post('money');
		$Trader  = Admin::model('trader.main');  
		$filters = array('Gameid' => $_SESSION[Config::SESSION_UID]);
		$trader = $Trader->findOne($filters);
		
		if($trader['Balance']<$money) {
			$this->error('可提现余额不足，请重新输入');
		}
		
		if(time()-$trader['WithdrawTime']<$Trader::WITHDRAWTIME) {
			$this->error('对不起，距上次提现时间未到5天，不能提现');
		}
		$Widthdraw = Admin::model('money.withdraw');
		$doc = array(
			'MoneyTotal' => $trader['Balance'],
			'Money' => $money,
			'Phone' => $trader['Phone'],
			'Gameid' => $trader['Gameid'],
			'Wechat' => $trader['Wechat'],
			'Time'   => time(),
			'Status' => $Widthdraw::STATUS_WAITING
		);	

		$Widthdraw->insert($doc);
		$trader['Balance'] -= $money;
		$trader['WithdrawTime'] = time();	
		$Trader->update($filters, $doc);
		$this->renderJSON(array('resutl'=>true)); 
	}

	//提现记录
	public function withdrawLogAction() {
		$pn = $this->request->get('pn');    
		$params['filters'] = array(
			'Gameid' => $_SESSION[Config::SESSION_UID]
		);

		$data = Admin::model('money.withdraw')->pagination($params, $pn);
		$this->renderJSON($data);
	}
}
