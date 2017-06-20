<?php
require(DOC_ROOT. '/control/BaseController.php');

class MoneyController extends BaseController {
    public function withdrawFormAction() {
        $filters = array('Gameid' => $_SESSION[Config::SESSION_UID]);    
        $trader  = Admin::model('trader.main')->findOne(
            $filters, 
            array(
                'Nickname'     => 1, 
                'Status'       => 1, 
                'Balance'      => 1
            )
        );

        $this->render('/money/withdraw-form.html', array(
            'data' => $trader
        ));
    }

    //申请提现
    public function withdrawAction() {
        $money     = (float)$this->request->post('money');
        $Widthdraw = Admin::model('money.withdraw');
        if(!($money>0)) {
            $this->error("请输入有效金额");
        }

        if($money>$Widthdraw::MAX_MONEY) {
            $MAX_MONEY = $Widthdraw::MAX_MONEY;
            $this->error("单笔最多只能提现{$MAX_MONEY}元");
        }

        $Trader  = Admin::model('trader.main');  
        $filters = array('Gameid' => $_SESSION[Config::SESSION_UID]);
        $trader = $Trader->findOne($filters);

        if(!$trader) {
            $this->error('请重新登录'); 
        }

        if($trader['Balance']<$money) {
            $this->error('可提现余额不足，请重新输入');
        }
        
        $filters   = array(
            'Gameid' => $trader['Gameid'],
        );

        $count = $Widthdraw->find(array_merge($filters, array(
                    'Time' => array(
                        '$gt'=> strtotime("today")
                    )
                 )))->count();
        
        if($count>=$Widthdraw::MAX_WITHDRAW) {
            $MAX_WITHDRAW = $Widthdraw::MAX_WITHDRAW;
            $this->error("每天只能提现{$MAX_WITHDRAW}次");
        } 
        $doc = array(
            'MoneyTotal' => $trader['Balance'], //提现前余额
            'Money'      => $money,             //提现金额
            'Phone'      => $trader['Phone'],
            'Gameid'     => $trader['Gameid'],
            'Wechat'     => $trader['Wechat'],
            'Time'       => time(),             //申请提现时间
            'Status'     => $Widthdraw::STATUS_WAITING //等待客服处理
        );	

        $Widthdraw->insert($doc);
        $Trader->update($filters, array(
            'Balance' => $trader['Balance'] - $money
        ));
        $this->renderJSON(array('resutl'=>true)); 
    }

    //提现记录
    public function withdrawLogAction() {
        $pn = $this->request->get('pn');    
        $params['filters'] = array(
            'Gameid' => $_SESSION[Config::SESSION_UID]
        );

        $data = Admin::model('money.withdraw')->pagination($params, $pn);
        $this->render('money/withdraw-log.html', array(
            'data' => $data
        ));
    }
    
	//提现到微信帐户
    public function wechatWithdrawAction() {
	
	}
    
	//提现到支付宝
	public function alipayWithdrawAction() {
	
	}
}
