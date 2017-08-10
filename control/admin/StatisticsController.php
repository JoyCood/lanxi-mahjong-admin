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
    
    } 

}
