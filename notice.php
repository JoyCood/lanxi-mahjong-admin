<?php !defined('TANG_FENG') AND exit('Access Denied!');

$app->get('/index.html', function() use($app){
	$notice = Admin::model('notice.main');
    $data = $notice->pagination();

	$app->render('notice/index.html', array(
	    'data' => $data
	));
});

//获取公告祥情
$app->get('/form.html', function() use($app){
    $id = $app->request->get('id', '');
	$data = array();
	if($id) {
		$filter = array('_id' => new MongoId($id));
	    $data = Admin::model('notice.main')->findOne($filter);
	}

	$app->render('notice/form.html', array(
		'data' => $data
	));
});

//创建或更新公告
$app->post('/form.html', function() use($app){
    $id = $app->request->post('id', '');
	
	$doc = array(
	    'Title'   => trim($app->request->post('title')),
		'Content' => trim($app->request->post('content')),
		'Expire'  => strtotime($app->request->post('expire')),
		'AdminId' => $_SESSION[Config::$session['REGION_ID']]
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

	$app->render('notice/list.html', array(
        'data' => $notice->pagination($app->request->post('__url'))	
	));
});

//删除公告
$app->post('/delete.html', function() use($app){
    $items = $app->request->post('items', array());
	$notice = Admin::model('notice.main');
    $notice->deleteById($items);	

	$app->render('notice/list.html', array(
        'data' => $notice->pagination($app->request->post('__url'))	
	));
});
