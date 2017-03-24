<?php !defined('TANG_FENG') AND exit('Access Denied!');

//代理商列表
$app->get('/index.html', function() use($app){
    $trader = Admin::model('trader.main');
	$data = $trader->pagination();
	$app->render('trader/index.html', array(
		'data' => $data,
        'wechat' => $app->request->get('wechat'),
		'phone'  => $app->request->get('phone'),
		'gameid' => $app->request->get('gameid')
	));
});

//代理商祥情
$app->get('/form.html', function() use($app){
    $id = $app->request->get('id', '');
	$data = array();
	if($id) {
	    $filters = array('_id' => new MongoId($id));
		$data = Admin::model('trader.main')->findOne($filters);
	}

	$app->render('trader/form.html', array(
	    $id    => $id,
	    'data' => $data
	));
});

//更新代理商资料
$app->post('/form.html', function() use($app){
	$id        = trim($app->request->post('id'));
    $password  = trim($app->request->post('password'));
	$password2 = trim($app->request->post('password2'));
	$status    = intval($app->request->post('status'));
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
	$app->render('trader/list.html', array(
        'data' => $trader->pagination($app->request->post('__url'))	
	));
});
