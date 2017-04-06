<?php define('TANG_FENG', TRUE);

define('DEBUG', TRUE);

define('DOC_ROOT', __DIR__);
define('DOC_DIR', substr(dirname($_SERVER['SCRIPT_NAME']), 0));
define('BASE_URL', DOC_DIR == DIRECTORY_SEPARATOR? '' : DOC_DIR);
define('LIB_DIR', dirname(__FILE__).'/lib/');

require('conf/db.config.php');
require('lib/Slim/Slim.php');
require('helper/helper.php');
require('src/admin.php');

date_default_timezone_set('Asia/Shanghai');

\Slim\Slim::registerAutoloader();
if(!DEBUG) {
    ini_set('display_errors', 'Off');
    error_reporting(0);

    $logWriter = new \Slim\LogWriter(fopen(LogDir. '/log-'. date('Y-m-d'). '.log', 'a'));
    $setting = array(
	    'log.enable' => false, 
	    'log.level'  => \Slim\Log::DEBUG, 
	    'log.writer' => $logWriter
    );
} else {
	ini_set('display_errors', 'On');
    error_reporting(E_ALL);

	$logWriter = new \Slim\LogWriter(fopen(LogDir. '/log-'. date('Y-m-d'). '.log', 'a'));
	$setting = array(
		'mode'       => 'production',
		'log.enable' => true, 
		'debug'      => true,
		'log.writer' => $logWriter
	);
}

$app = new \Slim\Slim($setting);
Admin::init($app);

session_start();

$app->hook('slim.before.router', function() use($app){
    $path = $app->request->getPathInfo();
	if(array_key_exists($path, Config::$routes)) {
		list($method, $control, $action) = explode('::', Config::$routes[$path]);
		$controlFile = DOC_ROOT. '/control/admin/'. $control. '.php';
		if(file_exists($controlFile)) {
			require($controlFile);
			$controller = new $control($app);
			if(method_exists($controller, $action)) {
				$app->map($path, array($controller, $action))->via($method);
			}
		}
	}

    if(!isset($_SESSION[Config::$session['USER']]) || !$_SESSION[Config::$session['USER']]) {
        if(!in_array($path, Config::$notauth)) {
			$dir = basename($_SERVER['SCRIPT_NAME'], '.php');
			$app->redirect(BASE_URL . "/{$dir}/login?from=" . urlencode($_SERVER['REQUEST_URI']));	
		}
    }
});

return $app;
