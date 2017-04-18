<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelTraderMain 
{
	const PASSWORD_SALT = 'TANG_FENG';

	public $status = array(
	    0, //正常
		1, //锁定
		2, //黑名单
		3  //待审核
    );

	private $fields = array(
        'Gameid',   #string  游戏id
	    'Phone',    #string  手机号
	    'Nickname', #string  昵称
	    'Wechat',   #string  微信号
	    'Auth',     #string  验证码
	    'Pwd',      #string  密码
	    'CIP',      #uint32  注册时的ip地址
	    'CTime',    #uint32  注册时间
		'JTime',    #uint32  绑定时间
	    'Addr',     #string  物理地址
	    'LIP',      #uint32  最后登录ip
		'LTime',    #uint32  最后登录时间
	    'Lv',       #uint32  代理等级
	    'Parent',   #string  上级代理id
	    'Charge',   #uint32  充值金额（单位/分）
	    'Status',   #uint32  状态  正常0  锁定1  黑名单2 待审核3
	    'Balance'   #uint32  可提现余额(单位/分)
	);

	public function collection() {
        return Admin::db('agent_user');	
	}

	public function findOne($filter, $projection=array()) {
	    return $this->collection()->findOne($filter, $projection);
	}

	//插入数据
	public function insert($data) {
		$data = Helper::allowed($data, $this->fields);
	    return $this->collection()->insert($data);
	}

	//更新数据
	public function update($filter, $data) {
		$data = Helper::allowed($data, $this->fields);
	    return $this->collection()->update($filter, array('$set' => $data));
	}

	public function pagination($params, $pnValue=null) {
		$pn     = Helper::popValue($params, 'pn', 1); 
		$sort   = Helper::popValue($params, 'sort', 'CTime');
		$order  = Helper::popValue($params, 'order', -1);
		$filters = array();

		//微信号搜索
		$wechat = Helper::popValue($params, 'Wechat');
		if($wechat) {
		    $filters['Wechat'] = ['$regex' => $wechat];
		}
		//电话搜索
		$phone = Helper::popValue($params, 'Phone');
		if($phone) {
		    $filters['Phone'] = ['$regex' => $phone];
		}
		//游戏ID
		$gameId = Helper::popValue($params, 'Gameid');
		if($gameId) {
		    $filters['Gameid'] = $gameId;
		}

		$data = Admin::pagination(
			$this->collection(),
			is_null($pnValue)? $pn: $pnValue,
			$filters,
			array($sort => intval($order) > 0? 1: -1)
	    );
        
		return $data;
	}

	public function password($pass) {
		return md5(self::PASSWORD_SALT. $pass);
	}

}
