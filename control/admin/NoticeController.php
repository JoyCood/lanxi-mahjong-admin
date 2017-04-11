<?php
require(DOC_ROOT. '/control/BaseController.php');

class NoticeController extends BaseController{
    public function inpourAction() {
        $notice = Admin::model('notice.main');
        $data = $notice->pagination();

        $this->render('notice/index.html', array(
            'data' => $data
        ));
    }

    // 获取公告祥情
    public function formAction() {
        $id = $this->request->get('id', '');
        $data = array();
        if($id) {
            $filter = array('_id' => new MongoId($id));
            $data = Admin::model('notice.main')->findOne($filter);
        }

        $this->render('notice/form.html', array(
            'data' => $data
        ));
    }

    // 创建或更新公告
    public function formSaveAction() {
        $id = $this->request->post('id', '');
        
        $doc = array(
            'Title'   => trim($this->request->post('title')),
            'Content' => trim($this->request->post('content')),
            'Expire'  => strtotime($this->request->post('expire')),
            'AdminId' => $_SESSION[SESSION_UID]
        );

        if(!$doc['Title']) {
            Admin::error('请输入标题。');
        }
        if(!$doc['Content']) {
            Admin::error('请输入内容。');
        }
        if(!$doc['Expire']) {
            Admin::error('请输入过期时间');
        }

        $notice = Admin::model('notice.main');

        if(!$id) {
            $doc['Time'] = time();
            $notice->insert($doc);
            print_r($doc);
        } else {
            $notice->update($doc, $id);
        }

        $this->render('notice/list.html', array(
            'data' => $notice->pagination($app->request->post('__url'))	
        ));
    }

    // 删除公告
    public function deleteAction() {
        $items = $this->request->post('items', array());
        $notice = Admin::model('notice.main');
        $notice->deleteById($items);	

        $this->render('notice/list.html', array(
            'data' => $notice->pagination($this->request->post('__url'))	
        ));
    }
}