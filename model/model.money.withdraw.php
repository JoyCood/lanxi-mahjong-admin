<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelMoneyWithdraw
{
    private $fields = array(
	    'MoneyTotal',  #uint32  可提现金额
		'Money',       #uint32  申请提现金额
		'Gameid',      #string  游戏id
		'Phone',       #string  手机号
		'Time',        #uint32  申请提现时间
		'Wechat'       #string  微信id
	);    
    
	public function collection() {
        return Admin::db('agent_rebate_record');	
	}
   

    public function findOne($filter=array(), $projection=array()) {
	    return $this->collection()->findOne($filter, $projection);
	}

	public function find($filter=array(), $projection=array()) {
	    return $this->collection()->find($filter, $projection);
	}

    public function pagination($url='', $pnValue=null) {
        $params = Helper::parseQueryString($url? $url: $_SERVER['REQUEST_URI']);
	    $pn     = Helper::popValue($params, 'pn', 1);
	    $sort   = Helper::popValue($params, 'sort', 'Time');
	    $order  = Helper::popValue($params, 'order', -1);	

		$filter = array();
		$start = Helper::popValue($params, 'start');
		if($start) {
		    $filters['$and'][] = array('Time' => array('$gte'=>$start));
		}

		$end = Helper::popValue($params, 'end');
		if($end) {
		    $filters['$and'][] = array('Time' => array('$lte'=>$end));
		}

		$gameId = Helper::popValue($params, 'gameId');
		if($gameId) {
		    $filters['Gameid'] = $gameId;
		}

		return Admin::pagination(
		    $url,
			$this->collection(),
			is_null($pnValue)? $pn: $pnValue,
			$filters,
			array($sort=> intval($order) > 0? 1: -1)
		);
	}
}
