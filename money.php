<?php !defined('TANG_FENG') AND exit('Access Denied!');

//订单列表
$app->get('/inpour/index.html', function() use($app){
	$data = Admin::model('money.inpour')->pagination();
	$app->render('money/inpour/index.html', array(
	    'data'  => $data,
		'start' => $app->request->get('start'),
		'end'   => $app->request->get('end')
	));	
});

//订单祥情
$app->get('/inpour/form.html', function() use($app){
    $id    = $app->request->get('id', '');
	$data = array();

	if($id) {
	    $filter = array('_id' => new MongoId($id));
	    $data = Admin::model('money.inpour')->findOne($filter);
	}

	$app->render('money/inpour/form.html', array(
		'data' => $data
	));
});

//返现申请列表
$app->get('/withdraw/index.html', function() use($app){
    $data = Admin::model('money.withdraw')->pagination();
	$app->render('money/withdraw/index.html', array(
	    'data'  => $data,
	    'start' => $start,
	    'end'   => $end	
	));
});

//返现申请祥情
$app->get('/withdraw/form.html', function() use($app){
	$id   = $app->request->get('id');
	if($id) {
		$filter = array('_id' => new MongoId($id));
		$data = Admin::model('money.withdraw')->findOne($filter);
    }

	$app->render('money/withdraw/form.html', array(
	    'data' => $data
	));
});

