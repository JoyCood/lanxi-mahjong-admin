<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelStatisticsMain {
    const NAME_DAU = 'DAU'; //日活用户
    const NAME_WAU = 'WAU'; //周活跃用户
    const NAME_MAU = 'MAU'; //月活跃用户数
    const NAME_DNU = 'DNU'; //当日注册用户数
    const NAME_DPU = 'DPU'; //日付费用户数
    const NAME_MPU = 'MPU'; //月付费用户数
    const NAME_USER_COUNTER = 'USER_COUNTER'; //总用户数统计

    public function collection() {
        return Admin::db('statistics');
    }

    public function find($filter, $projection=array()) {
        return $this->collection()->find($filter, $projection);
    }

    public function findOne($filter, $projection=array()) {
        return $this->collection()->findOne($filter, $projection);     
    }

    public function findAndModify($filter, $update, $projection=null, $options=array('new'=>true)) {
        return $this->collection()->findAndModify($filter, $update, $projection, $options);
    }

    //日活用户数统计(排重，当天注册并登录的用户未计算)
    public function DAU($openid) {
        $filters = array('Wechat_uid' => $openid);
        $projection = array('Last_login_time'=>1);
        $user = Admin::model('user.main')->findOne($filters, $projection);

        if(!empty($user['Last_login_time'])) {
            $date1 = date('Ymd', $user['Last_login_time']); 
            $date2 = date('Ymd', time());
            if($date1 < $date2) {
                $filters = array(
                    'name' => self::NAME_DAU, 
                    'date' => Helper::today()  
                ); 
                $update  = array(
                    '$set' => array(
                        'name' => self::NAME_DAU, 
                        'date' => Helper::today()
                    ), 
                    '$inc' => array('total' => 1)
                );
                $options = array('upsert' => true);
                return $this->findAndModify($filters, $update, null, $options);
            }
        }
        return false;
    } 

    //周活跃用户(排重，当天注册并登录的用户未计算)
    public function WAU($openid) {
        $filters = array('Wechat_uid' => $openid);
        $projection = array('Last_login_time' => $openid);
        $user = Admin::model('user.main')->findOne($filters, $projection);

        if(!empty($user['Last_login_time'])) {
            $date1 = date('Ymd', $user['Last_login_time']);
            $date2 = date('Ymd', time());

            $time1 = time() - $user['Last_login_time'];
            $time2 = 7 * 24 * 3600; //7天
            if($time1<$time2 && $date1<$date2) { //7天内登录过游戏的用户
                $filters = array(
                    'name' => self::NAME_WAU,
                    'date' => Helper::today()
                ); 
                $update = array(
                    '$set' => array(
                        'name' => self::NAME_WAU,
                        'date' => Helper::today()
                    ),
                    '$inc' => array(
                        'total' => 1
                    )
                );
                $options = array('upsert' => true);
                $this->findAndModify($filters, $update, null, $options);
            }
        }
    }

    //月活跃用户
    public function MAU($openid) {
        $filters = array('Wechat_uid' => $openid);
        $projection = array('Last_login_time' => $openid);
        $user = Admin::model('user.main')->findOne($filters, $projection);

        if(!empty($user['Last_login_time'])) {
            $date1 = date('Ymd', $user['Last_login_time']);
            $date2 = date('Ymd', time());

            $time1 = time() - $user['Last_login_time'];
            $time2 = 30 * 24 * 3600; //30天
            if($time1<$time2 && $date1<$date2) { //30天内登录过游戏的用户
                $filters = array(
                    'name' => self::NAME_MAU,
                    'date' => Helper::today()
                ); 
                $update = array(
                    '$set' => array(
                        'name' => self::NAME_MAU,
                        'date' => Helper::today()
                    ),
                    '$inc' => array(
                        'total' => 1
                    )
                );
                $options = array('upsert' => true);
                $this->findAndModify($filters, $update, null, $options);
            }
        }
    }

    //日注册用户数统计
    public function DNU() {
        $filters = array(
            'name' => self::NAME_DNU,
            'date' => Helper::today()
        );
        $update = array(
            '$set' => array(
                'name' => self::NAME_DNU,
                'date' => Helper::today()
            ),
            '$inc' => array(
                'total' => 1
            )
        );
        $options = array('upsert' => true);
        $this->findAndModify($filters, $update, null, $options);
    }

    //总用户数统计
    public function userCount() {
        $filters = array(
            'name' => self::NAME_USER_COUNTER
        ); 
        $update = array(
            '$inc' => array(
                'total' => 1
            )
        );
        $options = array('upsert' => true);
        $this->findAndModify($filters, $update, null, $options);
    }

    //当天付费用户数(排重)
    public function DPU($userid) {
        $MoneyInpour = Admin::model('money.inpour');
        $filters = array(
            'Userid' => $userid,
            'Result' => $MoneyInpour::SUCCESS,
            'Transtime' => array('$gte' => Helper::today()),
        );
        $order = $MoneyInpour->findOne($filters); 
        if(!$order) { //用户今天没有付过费
            $filters = array(
                'name' => self::NAME_DPU,
                'date' => Helper::today()
            ); 
			$update = array(
'$set' => array(
'name' => self::NAME_DPU,
'date' => Helper::today()
),
'$inc' => array(
'total' => 1
)
);
$options = array('upsert' => true);
$this->findAndModify($filters, $update, null, $options);
}
}

//月付费用户数(排重)
public function MPU($userid) {
$MoneyInpour = Admin::model('money.inpour');
$date1 = Helper::today();
$date2 = strtotime(date('Ym01', $date1));

$filters = array(
'Userid' => $userid,
            'Transtime' => array('$gte'=>$date2),
            'Result' => $MoneyInpour::SUCCESS 
        ); 
        $count = $MoneyInpour->find($filters)->count();
        if($count<2) {
            $filters = array(
                'name' => self::NAME_MPU,
                'date' => $date2
            );     
            $update = array(
                '$set' => array(
                    'name' => self::NAME_MPU,
                    'date' => $date2,
                ),
                '$inc' => array(
                    'total' => 1
                )
            );
            $options = array('upsert' => true);
            $this->findAndModify($filters, $update, null, $options);
        }
    }
}

