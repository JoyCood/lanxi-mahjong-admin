<?php !defined('TANG_FENG') AND exit('Access Denied!');

$app->get('/log/index.html', function() use($app){
		$data = Admin::model('admin.log')->pagination();
		$app->render('admin/log/index.html', array(
		    'data' => $data
		));
});
