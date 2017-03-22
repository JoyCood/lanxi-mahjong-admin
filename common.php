<?php define('TANG_FENG', TRUE);

define('DEBUG', TRUE);

define('DOC_ROOT', __DIR__);
define('DOC_DIR', substr(dirname($_SERVER['SCRIPT_NAME']), 0));
define('BASE_URL', DOC_DIR == DIRECTORY_SEPARATOR? '' : DOC_DIR);
define('LIB_DIR', dirname(__FILE__).'/lib/');

date_default_timezone_set('Asia/Shanghai');

if(!DEBUG) {
    ini_set('display_errors', 'Off');
    error_reporting(0);
}

require('conf/config.php');
require('lib/Slim/Slim.php');
require('helper/helper.php');
require('src/admin.php');
