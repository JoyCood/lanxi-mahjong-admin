<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelMoneyInpour
{
	private $allowed = array(
        'Transid',    #交易流水号
	    'Userid',     #用户id
	    'Itemid',     #购买商品id
	    'Amount',	  #购买商品数量
		'Money',      #交易总金额(单位为分)
		'Transtime',  #交易完成时间 yyyy-mm-dd hh24:mi:ss
		'Result',     #交易结果
		'Currency',   #货币类型
		'Paytype',    #支付方式
		'Clientip',   #客户端ip
		'Parent',     #上级代理商游戏id
		'Ctime',      #本条记录生成时间
		'Lv',         #代理商等级
		'Rebate',     #本次交易返点金额
	); 

	public function collection() {
	    return Admin::db('col_trade_record');
	}

	public function find($filter=array(), $projection=array()) {
        return $this->collection()->find($filter, $projection);	
	}
}
