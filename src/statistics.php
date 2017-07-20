<?php

class Statistics {

    const NAME_DAU = 'DAU'; //日活用户
    const NAME_WAU = 'WAU'; //周活跃用户
    const NAME_MAU = 'MAU'; //月活跃用户数
    const NAME_DNU = 'DNU'; //当日注册用户数
    const NAME_DPU = 'DPU'; //日付费用户数
    const NAME_USER_COUNTER = 'USER_COUNTER'; //总用户数统计

    public static function collection() {
        return Admin::db('statics');
    }

    //日活用户数统计(排重，当天注册并登录的用户未计算)
    public static function DAU($openid) {
        $filters = array('Wechat_uid' => $openid);
        $projection = array('Last_login_time'=>1);
        $user = Admin::model('user.main')->findOne($filters, $projection);
        if(isset($user['Last_login_time']) && $user['Last_login_time']>0) {
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
                return self::collection()->findAndModify($filters, $update, null, $options);
            }
        }
        return false;
    } 
    
    //周活跃用户(排重，当天注册并登录的用户未计算)
    public static function WAU($openid) {
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
                self::collection()->findAndModify($filters, $update, null, $options);
            }
        }
    }

    //月活跃用户
    public static function MAU($openid) {
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
                self::collection()->findAndModify($filters, $update, null, $options);
            }
        }
    }

    //日注册用户数统计
    public static function DNU() {
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
        self::collection()->findAndModify($filters, $update, null, $options);
    }

    //总用户数统计
    public static function userCount() {
        $filters = array(
            'name' => self::NAME_USER_COUNTER
        ); 
        $update = array(
            '$inc' => array(
                'total' => 1
            )
        );
        $options = array('upsert' => true);
        self::collection()->findAndModify($filters, $update, null, $options);
    }

    //当天付费用户数(排重)
    public static function DPU($userid) {
        $filters = array(
            'Userid' => $userid,
            'Result' => 0,
            'Transtime' => array('$gte' => Helper::today()),
        );
        $order = Admin::db('money.inpour')->findOne($filters); 
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
            self::collection()->findAndModify($filters, $update, null, $options);
        }
    }
}

