<?php
require(DOC_ROOT. '/control/BaseController.php');

class NoticeController extends BaseController{
    public function init() {
        $this->addViewData('MENU_ITEM', 'notice');
    }

    public function indexAction() {
        $this->render('notice/index.html', array(
        ));
    }

    public function listAction() {
        $notice = Admin::model('notice.main');
        $data = $notice->pagination();

        $this->render('notice/list.html', array(
            'data' => $data
        ));
    }

    // 获取公告祥情
    public function formAction() {
        $id  = $this->request->get('id', '');
        $doc = array();
        if($id) {
            $filter = array('_id' => new MongoId($id));
            $doc    = Admin::model('notice.main')->findOne($filter);
        }

        $this->render('notice/form.html', array(
            'data' => $doc
        ));
    }

    // 创建或更新公告
    public function formSaveAction() {
        $id  = $this->request->post('id', '');
        $doc = array(
            'Title'   => trim($this->request->post('Title')),
            'Content' => trim($this->request->post('Content')),
            'Expire'  => strtotime($this->request->post('Expire')),
            'AdminId' => $_SESSION[Config::SESSION_USER]
        );

        if(!$doc['Title']) {
            $this->error('请输入标题。');
        }
        if(!$doc['Content']) {
            $this->error('请输入内容。');
        }
        if(!$doc['Expire']) {
            $this->error('请输入过期时间');
        }

        $Notice = Admin::model('notice.main');
        if(!$id) {
            $doc['Time'] = time();
            $rs = $Notice->insert($doc);
            if($rs) {
                $doc['Id'] = (string)$rs['_id'];
            }
        } else {
            $rs = $Notice->update($doc, $id);
        }

        $this->renderJSON(array(
            'result' => (boolean)$rs,
            'data'   => $doc
        ));
    }

    // 删除公告
    public function deleteAction() {
        $Notice = Admin::model('notice.main');
        $items  = array($this->request->post('Id'));
        $result = $Notice->deleteById($items);	

        $this->renderJSON(array(
            'result' => (boolean)$result
        ));
    }
}