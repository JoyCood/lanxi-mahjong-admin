<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelTraderMain 
{
	public $status = array(
	    0, //正常
		1, //锁定
		2, //黑名单
		3  //待审核
    );

	private $allowed = array(
        'Gameid', //游戏id
	    'Phone',  //手机号
	    'Nickname', //昵称
	    'Wechat', //微信号
	    'Auth',   //验证码
	    'Pwd',    //密码
	    'CIP',    //注册时的ip地址
	    'CTime',  //注册时间
		'JTime',  //绑定时间
	    'Addr',   //物理地址
	    'LIP',    //最后登录ip
	    'LTime',  //最后登录时间
	    'Lv',     //代理等级
	    'Parent', //上级代理id
	    'Charge', //充值金额（单位：分）
	    'Status', //状态  正常0  锁定1  黑名单2 待审核3
	    'Balance' //可提现余额	
	);

	public function collection() {
        return Admin::db('agent_user');	
	}

	public function findOne($filter, $projection=array()) {
	    return $this->collection()->findOne($filter, $projection);
	}

	//更新数据
	public function update($filter, $data) {
		$data = Helper::allowed($data, $this->allowed);
	    return $this->collection()->update($filter, array('$set' => $data));
	}

	public function pagination($url = '', $pnValue=null) {
        $params = Helper::parseQueryString($url? $url: $_SERVER['REQUEST_URI']);	
		$pn     = Helper::popValue($params, 'pn', 1); 
		$sort   = Helper::popValue($params, 'sort', 'CTime');
		$order  = Helper::popValue($params, 'order', -1);
		$filters = array();

		//微信号搜索
		$wechat = Helper::popValue($params, 'wechat');
		if($wechat) {
		    $filters['Wechat'] = $wechat;
		}
        
		//电话搜索
		$phone = Helper::popValue($params, 'phone');
		if($phone) {
		    $filters['Phone'] = $phone;
		}
        
		//下级代理
		$gameId = Helper::popValue($params, 'gameId');
		if($gameId) {
		    $filters['Parent'] = $gameId;
		}

		$data = Admin::pagination(
		    $url,
			$this->collection(),
			is_null($pnValue)? $pn: $pnValue,
			$filters,
			array($sort => intval($order) > 0? 1: -1)
	    );
        
		return $data;
	}

}
