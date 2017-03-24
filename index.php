<?php 
require('common.php');
\Slim\Slim::registerAutoloader();

if(DEBUG) {
    $logWriter = new \Slim\LogWriter(fopen('logs/log', 'a'));
    $setting = array(
	    'log.enable' => FALSE, 
	    'log.level'  => \Slim\Log::DEBUG, 
	    'log.writer' => $logWriter
    );
} else {
	$setting = array(
		'mode' => 'production',
		'log.enable' => FALSE, 
		'debug' => FALSE
	);
}

$app = new \Slim\Slim($setting);

Admin::init($app);

session_start();

$app->hook('slim.before.router', function() use($app){
    $path = $app->request->getPathInfo();

    if(!isset($_SESSION[Config::$session['REGION_USER']]) || !$_SESSION[Config::$session['REGION_USER']]) {
        if(!in_array($path, array(
	        '/test.html',
	        '/login.html',
	        '/logout.html'
        ))) {
            $app->redirect(BASE_URL . '/login.html?from=' . urlencode($_SERVER['REQUEST_URI']));	
	    }
    }
});

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
		$_SESSION[Config::$session['REGION_ID']]    = $data['id'];
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

//公告
$app->group('/notice', function() use($app){
    require 'notice.php';
});

//代理商
$app->group('/trader', function() use($app){
    require 'trader.php';
});

$app->get('/test.html', function() use($app){
    $app->render('test.html');
});

$app->run();
