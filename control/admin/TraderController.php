<?php
require(DOC_ROOT. '/control/BaseController.php');

class TraderController extends BaseController{
    public function inpourAction() {
        $trader = Admin::model('trader.main');
        $data = $trader->pagination();
        $this->render('trader/index.html', array(
            'data' => $data,
            'wechat' => $this->request->get('wechat'),
            'phone'  => $this->request->get('phone'),
            'gameid' => $this->request->get('gameid')
        ));
    }

    // 代理商祥情
    public function formAction() {
        $id = $this->request->get('id', '');
        $data = array();
        if($id) {
            $filters = array('_id' => new MongoId($id));
            $data = Admin::model('trader.main')->findOne($filters);
        }

        $this->render('trader/form.html', array(
            $id    => $id,
            'data' => $data
        ));
    }

    // 更新代理商资料
    public function formSaveAction() {
        $id        = trim($this->request->post('id'));
        $password  = trim($this->request->post('password'));
        $password2 = trim($this->request->post('password2'));
        $status    = intval($this->request->post('status'));
        $doc = array();

        if($password) {
            if($password != $password2) {
                Admin::error('密码不一致。');
            }
            $doc['Pwd'] = md5($password);
        }

        $trader = Admin::model('trader.main');

        if(!in_array($status, $trader->status)) {
            Admin::error('请选择正确的帐户状态。');
        }

        $doc['Status'] = $status;
        $filters = array('_id' => new MongoId);
        $result = $trader->update($filters, $doc);
        $this->render('trader/list.html', array(
            'data' => $trader->pagination($this->request->post('__url'))	
        ));
    }

    public function deleteAction() {
    }
}