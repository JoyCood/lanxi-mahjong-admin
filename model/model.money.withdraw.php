<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelMoneyWithdraw
{
	const STATUS_WAITING = 0;
   	const STATUS_FINISH  = 1;
   
	private $fields = array(
	    'MoneyTotal',  #uint32  可提现金额
		'Money',       #uint32  申请提现金额
		'Gameid',      #string  游戏id
		'Phone',       #string  手机号
		'Time',        #uint32  申请提现时间
		'Wechat',      #string  微信id
		'Status',      #uint32  状态);    
	    'Operator'     #操作员
    );

	public function collection() {
        return Admin::db('agent_rebate_record');	
	}
   
	public function insert($data) {
        $data = Helper::allowed($data, $this->fields);
	    return $this->collection()->insert($data);	
	}

    public function findOne($filter=array(), $projection=array()) {
	    return $this->collection()->findOne($filter, $projection);
	}

	public function find($filter=array(), $projection=array()) {
	    return $this->collection()->find($filter, $projection);
	}

    public function pagination($params, $pnValue=null) {
	    $pn     = Helper::popValue($params, 'pn', 1);
	    $sort   = Helper::popValue($params, 'sort', 'Time');
	    $order  = Helper::popValue($params, 'order', -1);	

		$filters = Helper::popValue($params, 'filters', array());
		return Admin::pagination(
			$this->collection(),
			is_null($pnValue)? $pn: $pnValue,
			$filters,
			array($sort=> intval($order) > 0? 1: -1)
		);
	}
}
