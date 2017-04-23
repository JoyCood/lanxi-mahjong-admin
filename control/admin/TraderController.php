<?php
require(DOC_ROOT. '/control/BaseController.php');

class TraderController extends BaseController{
    protected $under = false;
    public function init() {
        $this->addViewData('MENU_ITEM', 'trader');
    }

    public function indexAction() {
        $this->render('trader/index.html');
    }

    public function listAction() {
        $Trader     = Admin::model('trader.main');
        $query      = $this->request->get('query');
        $keyword    = $this->request->get('keyword');
        $parent     = $this->request->get('parent');
        $parentData = array();

        $params = array();
        if($parent || $this->under) {
            $params['Parent'] = $parent? $parent: '*';
            if($params['Parent'] && $params['Parent'] != '*') {
                $parentData = $Trader->getOneById($params['Parent']);
            }
        }
        if($query && $keyword) {
            $params[$query] = $keyword;
        }
        $data = $Trader->pagination($params, intval($this->request->get('pn')));
        $this->render('trader/list.html', array(
            'data'       => $data,
            'query'      => $query,
            'keyword'    => $keyword,
            'parent'     => $parent,
            'parentData' => $parentData,
        ));
    }

    // 代理商详情
    public function formAction() {
        $id = $this->request->get('id', '');
        $data = array();
        if($id) {
            $filters = array('_id' => new MongoId($id));
            $data    = Admin::model('trader.main')->findOne($filters);
            if($data) {
                $id                 = (string)$data['_id'];
                $data['id']         = $id;
                $data['ParentData'] = array();
                unset($data['_id']);
                if($data['Parent']) {
                    $item = Admin::model('trader.main')->findOne(array(
                        '_id' => new MongoId($data['Parent'])
                    ));
                    if($item) {
                        $id                 = (string)$item['_id'];
                        $item['id']         = $id;
                        $data['ParentData'] = $item;
                    }
                }
            }
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
                        // 'Gameid' => trim($this->request->post('Gameid')),
                        // 'Phone'  => trim($this->request->post('Phone')),
                        // 'Wechat' => trim($this->request->post('Wechat')),
                        'Status' => intval($this->request->post('Status')),
                        // 'Parent' => trim($this->request->post('Parent')),
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
