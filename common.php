<?php define('TANG_FENG', TRUE);

define('DOC_ROOT', __DIR__);
define('DOC_DIR', substr(dirname($_SERVER['SCRIPT_NAME']), 0));
define('BASE_URL', DOC_DIR == DIRECTORY_SEPARATOR? '' : DOC_DIR);
define('LIB_DIR', dirname(__FILE__).'/lib/');

require('lib/Slim/Slim.php');
require('lib/ip/ip.php');
require('helper/helper.php');
require('helper/phone.php');
require('src/admin.php');
//require('src/statics.php');

date_default_timezone_set('Asia/Shanghai');

\Slim\Slim::registerAutoloader();
\Config::init();
if(DEBUG) {
	ini_set('display_errors', 'On');
    error_reporting(E_ALL);

	$logFile = Config::get('log', 'dir'). '/'. Config::get('log', 'file');
	$setting = array(
		'mode'       => 'development',
		'log.enable' => true, 
		'debug'      => true,
		'log.writer' => new \Slim\LogWriter(fopen($logFile, 'a')),
	);
} else {
	ini_set('display_errors', 'Off');
    error_reporting(0);

    $logFile = Config::get('log', 'dir'). '/'. Config::get('log', 'file');
    $setting = array(
        'mode' => 'production',
	    'log.enable' => true, 
        'debug'      => false,
	    'log.writer' => new \Slim\LogWriter(fopen($logFile, 'a')),
	    'log.level'  => \Slim\Log::DEBUG,
    );
}
$app = new \Slim\Slim($setting);
Admin::init($app);
Phone::init();

$sessionName = Config::get('settings', 'sessionName');
if($sessionName) {
    ini_set('session.name', $sessionName);
    ini_set('session.cookie_httponly', 1);
    session_start();
}

$app->hook('slim.before.router', function() use($app){
    $path   = $app->request->getPathInfo();
    $routes = Config::getOptions('routes');
    $dir    = basename($_SERVER['SCRIPT_NAME'], '.php');

    if(array_key_exists($path, $routes)) {
        list($method, $control, $action) = explode('::', $routes[$path]);
        $controlFile = DOC_ROOT. "/control/{$dir}/". $control. '.php';
        if(file_exists($controlFile)) {
            require($controlFile);
            $controller = new $control($app);
            if(method_exists($controller, $action)) {
                if($method) {
                    $app->map($path, array($controller, $action))->via($method);
                } else {
                    $app->map($path, array($controller, $action))->via('GET', 'POST');
                }
            }
        }
    }

    if(!isset($_SESSION[Config::SESSION_USER]) || !$_SESSION[Config::SESSION_USER]) {
        if(!in_array($path, Config::getOptions('notauth'))) {
            $app->redirect(BASE_URL . "/{$dir}/login?from=" . urlencode($_SERVER['REQUEST_URI']));	
	    }
    }
});

return $app;
