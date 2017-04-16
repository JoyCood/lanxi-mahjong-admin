<?php
require(DOC_ROOT. '/control/BaseController.php');

class TraderController extends BaseController{
    public function init() {
        $this->addViewData('MENU_ITEM', 'trader');
    }

    public function indexAction() {
        $trader = Admin::model('trader.main');
        $data = array(); //$trader->pagination();
        $this->render('trader/index.html', array(
            'data' => $data,
            'wechat' => $this->request->get('wechat'),
            'phone'  => $this->request->get('phone'),
            'gameid' => $this->request->get('gameid')
        ));
    }

    public function listAction() {
        $this->render('trader/list.html', array(
            // 'data' => $data,
            // 'wechat' => $this->request->get('wechat'),
            // 'phone'  => $this->request->get('phone'),
            // 'gameid' => $this->request->get('gameid')
        ));
    }

    // 代理商详情
    public function formAction() {
        $id = $this->request->get('id', '');
        $data = array();
        if($id) {
            $filters = array('_id' => new MongoId($id));
            $data    = Admin::model('trader.main')->findOne($filters);
        }

        $this->render('trader/form.html', array(
            'id'   => $id,
            'data' => $data
        ));
    }

    // 更新代理商资料
    public function formSaveAction() {
        $Trader   = Admin::model('trader.main');
        $id       = trim($this->request->post('Id'));
        $password = trim($this->request->post('Pwd'));
        $confirm  = trim($this->request->post('Cfm'));
        $doc      = array(
                        'Gameid' => trim($this->request->post('Gameid')),
                        'Phone'  => trim($this->request->post('Phone')),
                        'Wechat' => trim($this->request->post('Wechat')),
                        'Status' => intval($this->request->post('Status')),
                        'Parent' => intval($this->request->post('Parent')),
                    );
        if(empty($id)) {
            if(empty($password)) {
                $this->error('请输入密码');
            }
        }
        if($password) {
            if($password != $confirm) {
                $this->error('密码不一致');
            }
            $doc['Pwd'] = $Trader->password($password);
        }
        if(!in_array($doc['Status'], $Trader->status)) {
            $this->error('请选择正确的帐户状态');
        }

        if($id) {
            $filters = array('_id' => new MongoId($id));
            $result  = $Trader->update($filters, $doc);
        } else {
            $result  = $Trader->insert($doc);
        }

        $this->renderJSON((boolean)$result);
    }

    public function deleteAction() {
    }
}