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
        $start = strtotime($start);
        $end   = strtotime($end);
        
        //每日基础数据()
        $filters = array(
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
        $Statistics = Admin::model('statistics.main');
        $cursor = Admin::model('statistics.main')->find($filters, $projection)->sort($sort);
        foreach($cursor as $item) {
            $date = date('Ymd', $item['date']);
            echo "{$date} - {$item['name']} = {$item['total']}</br>"; 
        }

        //总用户数
        $filters = array('name' => $Statistics::NAME_USER_COUNTER);
        $projection = array('_id' => 0);
        $totalUser = $Statistics->findOne($filters, $projection);
        print_r($totalUser);
    }

}
