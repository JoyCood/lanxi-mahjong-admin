<?php
require(DOC_ROOT. '/control/AdminBaseController.php');

class MoneyController extends AdminBaseController{
    //订单列表
    public function inpourAction() {
        $this->addViewData('MENU_ITEM', 'inpour');
        $this->render('money/inpour/index.html', array(
        ));
    }

    public function inpourListAction() {
        $data = Admin::model('money.inpour')->pagination();
        $this->addViewData('MENU_ITEM', 'inpour');
        $this->render('money/inpour/list.html', array(
            'data'  => $data,
            'start' => $this->request->get('start'),
            'end'   => $this->request->get('end')
        ));
    }

    //订单详情
    public function inpourFormAction() {
        $id   = $this->request->get('id', '');
        $data = array();

        if($id) {
            $filter = array('_id' => new MongoId($id));
            $data = Admin::model('money.inpour')->findOne($filter);
        }
        $this->addViewData('MENU_ITEM', 'inpour');
        $this->render('money/inpour/form.html', array(
            'data' => $data
        ));
    }

    // 返现申请
    public function withdrawAction() {
        $this->addViewData('MENU_ITEM', 'withdraw');
        $this->render('money/withdraw/index.html', array(
        ));
    }
    // 返现申请列表
    public function withdrawListAction() {
        $data = Admin::model('money.withdraw')->pagination();
        $this->addViewData('MENU_ITEM', 'withdraw');
        $this->render('money/withdraw/list.html', array(
            'data'  => $data,
            // 'start' => $start,
            // 'end'   => $end	
        ));
    }
    // 返现汇总
    public function withdrawSummaryAction() {
        $data = Admin::model('money.withdraw')->pagination();
        $this->addViewData('MENU_ITEM', 'summary');
        $this->render('money/withdraw/summary.html', array(
            'data'  => $data,
            // 'start' => $start,
            // 'end'   => $end	
        ));
    }
}
