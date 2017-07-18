<?php
require(DOC_ROOT. '/control/AdminBaseController.php');

class UserController extends AdminBaseController {

	public function indexAction() {
	    $this->render('user/index.html');
	}

	//用户列表
	public function listAction() {
		$pn      = intval($this->request->get('pn', 1));
		$query   = trim($this->request->get('query'));
		$keyword = trim($this->request->get('keyword'));
        
		$params = array();
		if($query && $keyword) {
		    $params[$query] = $keyword;
		}
        $User = Admin::model('user.main');	
		$data = $User->pagination($params, $pn);

		$this->render('user/list.html', array(
	        'data'    => $data,	
			'query'   => $query,
			'keyword' => $keyword,
		));
	}

	//用户祥情
	public function formAction() {
        $id = $this->request->get('id', '');	
		$data = array();
		if($id) {
			$filters = array('_id' => $id);
	        $data = Admin::model('user.main')->findOne($filters);	
		}
		$this->render('user/form.html', array(
			'id'   => $id,
		    'data' => $data
		));
	}

	//修改用户资料
	public function formSaveAction() {
        $id = trim($this->request->post('id'));	
		$doc = array(
		    'Status' => intval($this->request->post('status'))
		);
		if(!in_array($doc['Status'], $User->status)) {
		    $this->error('请选择正确的帐户状态');
		}
		$filters = array('_id' => $id);
		$User->update($filters, $doc);

		$this->renderJSON(true);
	}
}
