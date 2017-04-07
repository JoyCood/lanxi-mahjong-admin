<?php 
require('conf/admin.config.php');

$app = require('common.php');
$app->run();

/**
$app->group('/admin', function() use($app) {
	$app->get('/login.html', function() use($app){
		$app->render('login.html', array(
			'from' => $app->request->get('from')
		));
	});

	$app->post('/login.html', function() use($app){
		$username = trim($app->request->post('username', ''));    
		$password = trim($app->request->post('password', ''));
		
		$admin = Admin::model('admin.main');
		$collection = Admin::db('admin');

		$data = $collection->findOne(array('username' => $username));

		if(!$data) {
			if($username == 'admin' && $password == 'admin') {
				$data = $admin->addUser(array(
						'username' => 'admin',
						'password' => $admin->password('admin'),
						'name'     => '管理员',
						'group'    => -1
				));
				$rs = true;
			} else {
				$rs = false;
			}
		} else {
			$password = $admin->password($password);
			if($password == $data['password']) {
				$data['id'] = (string)$data['_id'];
				unset($data['_id']);

				$rs = true;
			} else {
				$rs = false;
			}
		}

		if($rs) {
			$_SESSION[Config::$session['REGION_USER']]  = $data['username'];
			$_SESSION[Config::$session['REGION_GROUP']] = $data['group'];
			
			$collection->update(
				array('_id' => new MongoId($data['id'])),
				array('$set' => array(
					'last_login' => time(),
					'last_ip' => Admin::getRemoteIP()
				))
			);
		}
		$app->view()->renderJSON(array(
			'result' => $rs
		));
	});

	$app->get('/test.html', function() use($app){
		$app->render('test.html');
	});
});
**/

