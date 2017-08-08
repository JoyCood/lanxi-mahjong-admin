<?php !defined('TANG_FENG') AND exit('Access Denied!');
require(DOC_ROOT. '/control/BaseController.php');

class StatisticsController extends BaseController {

    //每日基础数据
    public function dailyDataAction() {
        $start = $this->request->post('start'); 
        $end   = $this->request->post('end');

        if(empty($start)) {
            $start = strtotime('-7 days');
            $start = date('Ymd 00:00:00', $start);
        }
        if(empty($end)) {
            $end = Helper::today();
            $end = date('Ymd 00:00:00', $end);
        }
        $start = new MongoDate(strtotime($start));
        $end   = new MongoDate(strtotime($end));
        
        $Statistics = Admin::model('statistics.main');
        $filters = [
            ['$match' => ['$and'=> [['Time'=> ['$gte'=>$start, '$lte'=>$end]]]]],
            ['$group' => ['_id' => ['UserId'=>'$UserId','month'=> ['$month'=>'$Time'], 'day'=> ['$dayOfMonth'=>'$Time'], 'year'=> ['$year'=>'$Time']], 'count'=>['$sum'=>1]]],
            //['$count' => 'count']
        ];

        $res = Admin::db('login_log')->aggregate($filters);
        print_r($res);
        exit();
        //每日基础数据()
        $filters = array(
            'name' => array(
                '$in' => array(
                    $Statistics::NAME_DAU,
                    $Statistics::NAME_DNU,
                    $Statistics::NAME_DPU,
                )
            ),
            '$and' => array(
                array(
                'date' => array(
                    '$gte'=>$start,
                    '$lte'=>$end
                ))
            )
        );
        $projection = array('_id'=>0);
        $sort = array('date' => -1);
        $cursor = Admin::model('statistics.main')->find($filters, $projection)->sort($sort);
        foreach($cursor as $item) {
            $date = date('Ymd', $item['date']);
           // echo "{$date} - {$item['name']} = {$item['total']}</br>"; 
        }

        //总用户数
        $filters = array('name' => $Statistics::NAME_USER_COUNTER);
        $projection = array('_id' => 0);
        $totalUser = $Statistics->findOne($filters, $projection);
		$this->render('statistics/daily-user.html', array(
		
		));
    }

}
