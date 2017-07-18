<?php

class Statistics {

    public static function collection() {
        return Admin::db('statics');
    }

    //日活用户数统计
    public static function DAU($openid) {
        $filters = array('Wechat_uid' => $openid);
        $projection = array('Last_login_time'=>1);
        $user = Admin::model('user.main')->findOne($filters, $projection);
        if(isset($user['Last_login_time']) && $user['Last_login_time']>0) {
            $date1 = date('Ymd', $user['Last_login_time']); 
            $date2 = date('Ymd', time());
            if($date1<$date2) {
                $filters = array(
                    'name' => 'DAU', 
                    'date' => Helper::today()  
                ); 
                $update  = array('$inc' => array('total' => 1));
                $options = array('$upsert' => true);
                return self::collection()->findAndModify($filters, $update, null, $options);
            }
        }
        return false;
    } 
    
    //周活跃用户
    public static function WAU($openid) {
        $filters = array('Wechat_uid' => $openid);
        $projection = array('Last_login_time' => $openid);
        $user = Admin::model('user.main')->findOne($filters, $projection);
        if(isset($user['Last_login_time']) && $user['Last_login_time']>0) {
            $time1 = time() - $user['Last_login_time'];
            $time2 = 7 * 24 * 3600; //7天
            if($time1<$time2) { //7天内登录过游戏的用户
                $filters = array(
                    'name' => 'WAU',
                    'date' => Helper::today()
                ); 
                $update = array('$inc' => 'total' => 1);
                $options = array('$upsert' => true);
                self::collection()->findAndModify($filters, $update null, $options);
            }
        }
    }

    //月活跃用户
    public static function MAU($openid) {
        $filters = array('Wechat_uid' => $openid);
        $projection = array('Last_login_time' => $openid);
        $user = Admin::model('user.main')->findOne($filters, $projection);
        if(isset($user['Last_login_time']) && $user['Last_login_time']>0) {
            $time1 = time() - $user['Last_login_time'];
            $time2 = 30 * 24 * 3600; //30天
            if($time1<$time2) { //30天内登录过游戏的用户
                $filters = array(
                    'name' => 'MAU',
                    'date' => Helper::today()
                ); 
                $update = array('$inc' => 'total' => 1);
                $options = array('$upsert' => true);
                self::collection()->findAndModify($filters, $update, null, $options);
            }
        }
    }

    //日注册用户数统计
    public static function DAR() {
        $filters = array(
            'name' => 'DAR',
            'date' = Helper::today()
        );
        $update = array('$inc' => array('total' => 1));
        $options = array('$upsert' => true);
        self::collection()->findAndModify($filters, $update, null, $options);
    }

    //总用户数统计
    public static function userCount() {
        $filters = array(
            'name' => 'user_count'
        ); 
        $update = array('$inc' => array('total' => 1));
        $options = array('$upsert' => true);
        self::collection()->findAndModify($filters, $update, null, $options);
    }

    //当天付费用户数(排重)
    public static function DPU($userid) {
        $filters = array(
            'Userid' => $userid,
            'Result' => 0,
            'Transtime' => array('$gte' => Helper::today()),
        );
        $order = Admin::db('money.inpour').findOne($filters); 
        if(!$order) {
            $filters = array(
                'name' => 'DPU',
                'date' => Helper::today()
            ); 
            $update = array('$inc' => array('total' => 1));
            $options = array('$upsert' => true);
            self::collection()->findAndModify($filters, $update, null, $options);
        }
    }
}

