<?php !defined('TANG_FENG') AND exit('Access Denied!');
require(DOC_ROOT. '/control/BaseController.php');

class StatisticsController extends BaseController {

    //每日基础数据
    public function dailyDataAction() {
        $start = $this->request->post('start'); 
        $end   = $this->request->post('end');

        if(empty($start)) {
            $start = strtotime('-7 days midnight');
        }
        if(empty($end)) {
            $end = strtotime('today midnight');
        }
        $tmpStart = $start;
        $tmpEnd = $end;
        $DAU = array();
        $DNU = array();
        while($tmpStart < $tmpEnd) {
            $date = date('Y-m-d', $tmpStart);
            $DAU[$date] = 0;
            $DNU[$date] = 0;
            $tmpStart = strtotime('+1 days', $tmpStart);
        }

        $DAU = array_merge($DAU, $this->DAU($start, $end));
        $DNU = array_merge($DNU, $this->DNU($start, $end));
		$this->render('statistics/daily-user.html', array(
		
		));
    }

    protected function DAU($start, $end) {
        $start = new MongoDate($start);
        $end   = new MongoDate($end);
        
        $DAU = array();
        $filters = [
            ['$match' => ['$and'=> [['Time'=> ['$gte'=>$start, '$lte'=>$end]]]]],
            ['$group' => ['_id' => ['date'=> ['$dateToString'=>['format'=>'%Y-%m-%d', 'date'=>'$Time']]], 'count'=>['$sum'=>1]]],
        ];

        $res = Admin::db('login_log')->aggregate($filters);
        foreach($res['result'] as $item) {
            $DAU[$item['_id']['date']] = $item['count'];
        }
        return $DAU;
    }

    protected function DNU($start, $end) {
        $start = new MongoDate($start);
        $end   = new MongoDate($end); 

        $DNU = array();
        $filters = [
            ['$match' => ['$and'=> [['Time'=> ['$gte'=>$start, '$lte'=>$end]]]]],
            ['$group' => ['_id' => ['date'=> ['$dateToString'=>['format'=>'%Y-%m-%d', 'date'=>'$Create_time']]], 'count'=>['$sum'=>1]]],
        ];

        $res = Admin::model('user.main')->collectioin()->aggregate($filters);
        foreach($res['result'] as $item) {
            $DNU[$item['_id']['date']] = $item['count'];
        }
        return $DNU;
    }

    protected function DPU($start, $end) {
     
    }

    protected function WAU($start, $end) {
        $start = strtotime('-4 weeks midnight');
        $end   = strtotime('today midnight');
        $timestamp = $end - $start;
        $days = $timestamp/(3600*24);
        $weeks = ceil($days/7);
        $client = new MongoClient();
        $colleciton = $client->selectCollection('lanxi_db', 'test');
        for($i=0; $i<$weeks; $i++) {
            $end2  = strtotime('+7 days', $start);
            $end3  = $end2 > $end ? $end : $end2;

            $start2 = new MongoDate($start);
            $end4   = new MongoDate($end3);
            $filters = array('$and'=>array(array('Time'=>array('$gte'=>$start2, '$lt'=>$end4))));
            $count = $colleciton->find($filters, array('_id'=>1))->count(); 
            echo "{$count}<br/>";
            $start = $end3;
        }
    } 

    protected function MAU($start, $end) {
        $start = strtotime('-4 months midnight');
        $start = strtotime('first day of this month midnight', $start);
        $end   = strtotime('first day of this month midnight');
        $timestamp = $end - $start;
        $months = floor($timestamp/(3600*24*30));
        $client = new MongoClient();
        $days = floor(4.5);
        $collection = $client->selectCollection('lanxi_db', 'test');
        for($i=0; $i<$months; $i++) {
            date('Y-m-d H:i:s', $start);
            $end2 = strtotime('first day of next month midnight', $start);
            $start2 = new MongoDate($start);
            $end3   = new MongoDate($end);
            $filters = array('$and'=>array(array('Time'=>array('$gte'=>$start2, '$lt'=>$end3))));
            $count = $collection->find($filters)->count();
            $start = $end2;
        }
    }

}
