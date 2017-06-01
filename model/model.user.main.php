<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelUserMain
{
	const PLATFORM_WECHAT = 1;
	const INIT_ROOM_CARD  = 4;
    const PLAYER = 0;
    const TRADER = 1;
    
    const STATUS_NORMAL    = 1;
    const STATUS_LOCKED    = 2;
    const STATUS_BLACKLIST = 3;

    private $fields = array(
        '_id',           #string 用户id
        'Nickname',      #string 昵称
        'Sex',           #uint32 用户性别,男1 女2 非男非女3
        'Sign',          #string 签名
        'Email',         #string 邮箱
        'Phone',         #string 手机号码
        'Auth',          #string
        'Pwd',           #string 密码
        'Birth',         #uint32 生日（unix时间戳）
        'Create_ip',     #uint32 注册时的ip地址
        'Create_time',   #uint32 注册时间(unix 时间戳)
        'Coin',          #uint32
        'Exp',           #uint32 经验
        'Diamond',       #uint32 钻石
        'Ticket',        #uint32 入场券
        'Exchange',      #uint32 兑换券
        'Terminal',      #string
        'Status',        #uint32 屏蔽状态(正常1  锁定2  黑名单3)
        'Address',       #string 物理地址
        'Photo',         #string 头像
        'Qq_uid',        #string QQ平台openid
        'Wechat_uid',    #string  微信平台openid
		'Wechat_unionid', #string 微信平台unionid
        'Microblog_uid', #string 微博平台openid
        'Vip',           #uint32 是否为vip
        'VipExpire',     #uint32 vip过期时间
        'Win',           #uint32 赢的局数
        'Lost',          #uint32 输的局数
        'Ping',          #uint32 平局数
        'Platform',      #uint32 登录平台0：手机登录，1：微信登录
        'ChenmiTime',    #uint32
        'Chenmi',        #uint32
        'Sound',         #boolean 是否开启声音
        'Robot',         #boolean 是否机器人
        'RoomCard',      #uint32 房卡数量
        'Build',         #string 绑定代理商id
        'BuildTime',     #uint32 绑定时间
        'FyAccountId',   #string
        'FyAccountPwd',  #string
        'IsTrader',      #uint32 //是否为代理商(self::PLAYER普通玩家 self::TRADER代理商) 
		'Last_login_time', #uint32 最后登录时间
	    'Last_Login_ip', #uint32 最后登录IP	
    );  

    public function collection() {
        return Admin::db('user');
    }

	public function insert($data) {
	    $data = Helper::allowed($data, $this->fields);
		return $this->collection()->insert($data);
	}

    public function findOne($filter, $projection=array()) {
        return $this->collection()->findOne($filter, $projection);
    }

    public function find($filter, $projection=array()) {
        return $this->collection()->find($filter, $projection);
    }

    public function update($filter, $data) {
        $data = Helper::allowed($data, $this->fields); 
        return $this->collection()->update($filter, array('$set'=>$data));
    }

	public function findAndModify($filter, $data, $projection=null, $options=array('new'=>true)) {
		$data = array('$set'=>$data);
		return $this->collection()->findAndModify($filter, $data, $projection, $options);
	}

    public function pagination($params, $pnValue=null) {
        $pn      = Helper::popValue($params, 'pn', 1);    
        $sort    = Helper::popValue($params, 'sort', 'createtime');
        $order   = Helper::popValue($params, 'order', -1);
        $filters = Helper::popValue($params, 'filters', array());

        $data = Admin::pagination(
            $this->collection(),
            is_null($pnValue)? $pn: $pnValue,
            $filters,
            array($sort => intval($order) > 0? 1: -1)
        );

        return $data;
    }

}
