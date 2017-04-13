<?php !defined('TANG_FENG') AND exit('Access Denied!');

class Admin {
    static protected $app;
    static protected $db;

    static public function init($app) {
        require 'lib/Twig/Autoloader.php';
		Twig_Autoloader::register();

		require 'src/twig.php';
		$twigView = new View\Twig();
		$twigView->setConfig(Config::get('settings', 'slim'));

		$app->config(array_merge(Config::get('settings', 'slim'), array(
			'view' => $twigView	
		)));

		self::$app = $app;
    }
    
    //选择collection
    static public function db($collection) {
        if(!is_object(self::$db)) {
			$dbConfig = Config::getOptions('db');
			$server   = "mongodb://{$dbConfig['host']}:{$dbConfig['port']}";
			$mongo    = new MongoClient($server, array(
				'connect' => $dbConfig['connect']
			));
			self::$db = $mongo->selectDB($dbConfig['name']);
		}

		$allowedCollections = Config::get('db', 'collections');
		if(in_array($collection, $allowedCollections)) {
			return self::$db->$collection;
		} else {
			return null;
		}
    }

    //加载model
    static public function model($name) {
		static $instance = array();
		$class = str_replace('.', '', $name);
		$class = 'Model'. str_replace(' ', '', ucfirst($class));

		if(!isset($instance[$name])) {
			if(!class_exists($class)) {
				require(DOC_ROOT. '/model/model.'. $name . '.php');
			}
			$instance[$name] = new $class;
		}
		return $instance[$name];
    }

    static public function getPermission() {
		static $privileges = null;
		static $timestamp  = 0;
		$time = time();
		if($timestamp < $time - 10) {
			$perm       = SwimAdmin::model('permission.main');
			$privileges = $perm->getPermission($_SESSION[S_USER]);
			$timestamp  = $time;
		}

		return $privileges;
		}

		static public function getPermissionByMod($mod) {
		$privileges = self::getPermission();
		if($privileges && isset($privileges[$mod])) {
			return $privileges[$mod];
		} else {
			return 0;
		}
    }

    static public function checkPermission($mod, $val = null, $error = true) {
		$privileges = self::getPermission();
		$result     = 0;
		$val        = is_null($val)? PERM_READ: $val;
		if($privileges && isset($privileges[$mod])) {
			$result = $privileges[$mod] & $val;
		}
		if(!$result) {
			if($error) {
				self::error('对不起，您没有权限进行此操作。', '');
			} else {
				return false;
			}
		}
		return true;
    }

    static public function combine($ary1, $ary2) {
		$ary = array();
		foreach($ary1 as $key => $val) {
			if(isset($ary2[$key])) {
				$val = $ary2[$key];
			}
			$ary[$key] = $val;
		}
		return $ary;
    }
    
    //获取客户端IP
    static public function getRemoteIP () {
        if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && strlen($_SERVER["HTTP_X_FORWARDED_FOR"]) > 0) { 
			$f = $_SERVER["HTTP_X_FORWARDED_FOR"];
			$reserved = false;
			if (substr($f, 0, 3) == "10.") {
				$reserved = true;
			}
			if (substr($f, 0, 4) == "172." && substr($f, 4, 2) > 15 && substr($f, 4, 2) < 32) {
				$reserved = true;
			}
			if (substr($f, 0, 8) == "192.168.") {
				$reserved = true;
			}
			if (!$reserved) {
				$ip = $f;
			}
		} 
		if (!isset($ip)) {
			$ip = $_SERVER["REMOTE_ADDR"];
		}
		return $ip;
    }
}
