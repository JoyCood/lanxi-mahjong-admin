<?php
require(DOC_ROOT. '/control/BaseController.php');

class MoneyController extends BaseController{
    //订单列表
    public function inpourAction() {
        $data = Admin::model('money.inpour')->pagination();
        $this->render('money/inpour/index.html', array(
            'data'  => $data,
            'start' => $this->request->get('start'),
            'end'   => $this->request->get('end')
        ));
    }

    //订单祥情
    public function inpourFormAction() {
        $id   = $this->request->get('id', '');
        $data = array();

        if($id) {
            $filter = array('_id' => new MongoId($id));
            $data = Admin::model('money.inpour')->findOne($filter);
        }
        $this->render('money/inpour/form.html', array(
            'data' => $data
        ));
    }

    //返现申请列表
    public function withdrawAction() {
        $data = Admin::model('money.withdraw')->pagination();
        $this->render('money/withdraw/index.html', array(
            'data'  => $data,
            'start' => $start,
            'end'   => $end	
        ));
    }
}