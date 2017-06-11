<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelMoneyInpour
{
	const SUCCESS = 0;    //交易成功
	const FAILURE = 1;    //交易失败
	const PROCESSING = 2; //交易中
	const DELIVER = 3;    //发货中

	const WEIXIN = 1;      //微信支付 

	const GOODS_TYPE_ROOMCARD = 4; //商品类型：房卡

	private $fields = array(
        'Transid',    #string  交易流水号
	    'Userid',     #string  用户id
	    'Itemid',     #string  购买商品id
	    'Amount',	  #string  购买商品数量
        'Quantity',   #uint32  房卡数量
		'Money',      #uint32  交易总金额(单位为分)
		'Transtime',  #string  交易完成时间 yyyy-mm-dd hh24:mi:ss
		'Result',     #int     交易结果(0交易成功，1交易失败，2交易中，3发货中)
		'Currency',   #string  货币类型
		'Paytype',    #uint32  支付方式
		'Clientip',   #string  客户端ip
		'Parent',     #string  上级代理商游戏id
		'Ctime',      #int64   本条记录生成时间
		'Lv',         #uint32  代理商等级
		'Rebate',     #uint32  本次交易返点金额
        'NotifyRes',  #string  支付回调结果
	); 

	public function collection() {
	    return Admin::db('col_trade_record');
	}

    public function insert($data) {
        $data = Helper::allowed($data, $this->fields);
        return $this->collection()->insert($data); 
    }

    public function update($filter, $data) {
        $data = Helper::allowed($data, $this->allowed);
        return $this->collection()->update($filter, array('$set'=>$data)); 
    }

	public function findAndModify($filter, $data, $projection, $options) {
	    return $this->collection()->findAndModify($filter, $data, $projection, $options);
	}

	public function find($filter=array(), $projection=array()) {
        return $this->collection()->find($filter, $projection);	
	}

	public function findOne($filter=array(), $projection=array()) {
        return $this->collection()->findOne($filter, $projection);	
	}

	//分页
	public function pagination($params = array(), $pnValue=null) {
	    $pn    = Helper::popValue($params, 'pn', 1);
	    $sort  = Helper::popValue($params, 'sort', 'CTime');	
		$order = Helper::popValue($params, 'order', -1); 
	
		$filters = array();

		$start = Helper::popValue($params, 'start');
		if($start) {
		    $filters['$and'][] = array('Ctime' => array('$gte'=>$start));
		}

		$end = Helper::popValue($params, 'end');
		if($end) {
		    $filters['$and'][] = array('Ctime' => array('$lte'=>$end));
		}
		
		return Admin::pagination(
			$this->collection(),
			is_null($pnValue)? $pn: $pnValue,
			$filters,
			array($sort=> intval($order) > 0? 1: -1)
		);
	}
}
