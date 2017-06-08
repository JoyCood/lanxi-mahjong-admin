<?php
namespace View;

use Slim\View as SlimView;

class Twig extends SlimView
{
	protected $twig;

	/**
	 * Get Twig Engine.
	 */
	public function getTwig()
	{
		if($this->twig)
		{
			return $this->twig;
		}

		$loader = new \Twig_Loader_Filesystem($this->getTemplatesDirectory());
		$twig = new \Twig_Environment($loader);

		$twig->addGlobal('BASE_URL', BASE_URL. '/');
		$twig->addGlobal('CONFIG', \Config::getOptions('settings'));
		//$twig->addGlobal('_Storage', \Config::$storage);
		// $twig->addGlobal('_Admin', $this->config['admin']);
		$twig->addGlobal('AJAX', 
			(isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))
		);
		$twig->addExtension(new SwimTwigExtension());

		return $this->twig = $twig;
	}



	public function addGlobal($data, $value = null) {
		if(!is_array($data)) {
			$key = $data;
			$data = array();
			$data[$key] = $value;
		}
		
		$twig = $this->getTwig();
		foreach($data as $key => $value) {
			$twig->addGlobal($key, $value);
		}
	}

	public function setConfig(&$config) {
		$this->config = $config;
	}

	/**
	 * Render a template file by Twig
	 * @param  string  $template    The template pathname, relative to the template base directory
	 * @return string               The rendered template
	 */
	public function render($template, $data=null)
	{
		$twig = $this->getTwig();
		if(is_numeric($template)) {
			require(DOC_ROOT. '/conf/http.code.php');
			$code 		= $template;
			$template 	= '/error.html';
			if(is_null($data)) {
				$message = \Config_HTTT_Status::getStatus($code);
			} else {
				$message = $data;
			}
			$data = array(
				'code' 	=> $code,
				'message'	=> $message
			);
			return $twig->render($template, $data);
		} else {
			header('APP-STATE: APP');
			return $twig->render($template, $this->data->all());
		}
	}

	public function renderJSON($ary) {
		header('Content-Type: application/json');
		header('APP-STATE: APP');
		echo json_encode($ary);
		exit;
	}
}

/**
 * Twig 扩展
 */
class SwimTwigExtension extends \Twig_Extension {
	public function getName() {}
	public function getFilters() {
		return array(
			'status'               => new \Twig_Filter_Method($this, 'ft_status', array('is_safe' => array('html'))),
			'order_process_status' => new \Twig_Filter_Method($this, 'ft_order_process_status', array('is_safe' => array('html'))),
			'restype'              => new \Twig_Filter_Method($this, 'ft_res_type', array('is_safe' => array('html'))),
			'trader_status'        => new \Twig_Filter_Method($this, 'ft_trader_status', array('is_safe' => array('html'))),
			'user_status'          => new \Twig_Filter_Method($this, 'ft_user_status', array('is_safe' => array('html'))),
			'money_status'         => new \Twig_Filter_Method($this, 'ft_money_status', array('is_safe' => array('html'))),
			'zerofill'             => new \Twig_Filter_Method($this, 'ft_zerofill'),
			'dump'                 => new \Twig_Filter_Method($this, 'ft_dump', array('is_safe' => array('html'))),
			'datetime'             => new \Twig_Filter_Method($this, 'ft_datetime'),
			'truncate'             => new \Twig_Filter_Method($this, 'ft_truncate'),
		);
	}

	public function getFunctions() {
		return array(
			// 'json'  => new Twig_Function_Method($this, 'json_encode')
			'is_video' 	     => new \Twig_Function_Method($this, 'fn_isVideo'),
			'image_src'      => new \Twig_Function_Method($this, 'fn_imageSrc'),
			'check_perm'     => new \Twig_Function_Method($this, 'fn_checkPermission'),
			'session'        => new \Twig_Function_Method($this, 'fn_session'),
			'pagination_url' => new \Twig_Function_Method($this, 'fn_pagination_url'),
			'url_path'       => new \Twig_Function_Method($this, 'fn_url_path'),
			'csrf_input'     => new \Twig_Function_Method($this, 'fn_csrf_input', array('is_safe' => array('html'))),

		);
	}

	public function ft_datetime($val) {
		return $val? date('Y-m-d H:i:s', $val): '';
	}

	public function ft_status($value) {
		return $value?
			'<span class="fa fa-check text-success" title="可用"></span>':
			'<span class="fa fa-times text-danger" title="停用"></span>';
	}

	public function ft_order_process_status($order) {
		$value = '-';
		if($order['buyer_process_status'] == \ModelCourseOrder::BUYER_PROCESS_STATUS_ASK_REFUND) {
			if($order['seller_process_status'] == \ModelCourseOrder::SELLER_PROCESS_STATUS_NORMAL) {
				$value = '<span style="color: #c09853;">申请退款</span>';
			}
		}
		if($order['seller_process_status'] == \ModelCourseOrder::SELLER_PROCESS_STATUS_REFUND_SUCESS) {
			$value = '<span style="color: #b94a48;">退款成功</span>';
		} else if($order['seller_process_status'] == \ModelCourseOrder::SELLER_PROCESS_STATUS_PROCESSING) {
			$value = '<span style="color: #3a87ad;">退款处理中</span>';
		}
		return $value;
	}

	public function ft_truncate($value, $len = 100) {
		$output = mb_substr($value, 0, $len, 'UTF-8');
		if(strlen($value) > $len) {
			$output .= '...';
		}
		return $output;
	}

	public function ft_zerofill($value, $padLength = 2) {
		return str_pad($value, $padLength, '0');
	}

	public function ft_res_type($value) {
		$rs = '';
		if($value & 1) {
			$rs = '<i class="fa fa-image"></i> ';
		} else if($value & 2) {
			$rs = '<i class="fa fa-video-camera"></i> ';
		}
		return $rs;
	}

	public function ft_user_status($value) {
		$status = array(
			\ModelUserMain::STATUS_NORMAL    => '<span class = "text-success">正常</span>',
			\ModelUserMain::STATUS_LOCKED    => '<span class = "text-warning">锁定</span>',
			\ModelUserMain::STATUS_BLACKLIST => '<span class = "text-danger">黑名单</span>',
			'N/A'                             => '<span style =" color: #999;">N/A</span>',
		);
		return isset($status[$value])? $status[$value]: $status['N/A'];
	}

	public function ft_money_status($value) {
		$status = array(
			\ModelMoneyWithdraw::STATUS_WAITING    => '<span class = "text-info">待处理</span>',
			\ModelMoneyWithdraw::STATUS_FINISH    => '<span class = "text-success">已返现</span>',
			'N/A'                             => '<span style =" color: #999;">N/A</span>',
		);
		return isset($status[$value])? $status[$value]: $status['N/A'];
	}

	public function ft_trader_status($value) {
		$status = array(
			\ModelTraderMain::STATUS_NORMAL    => '<span class = "text-success">正常</span>',
			\ModelTraderMain::STATUS_LOCKED    => '<span class = "text-warning">锁定</span>',
			\ModelTraderMain::STATUS_BLACKLIST => '<span class = "text-danger">黑名单</span>',
			\ModelTraderMain::STATUS_WAITING   => '<span class = "text-muted">待审核</span>',
			'N/A'                             => '<span style =" color: #999;">N/A</span>',
		);
		return isset($status[$value])? $status[$value]: $status['N/A'];
	}

	public function fn_isVideo($src) {
		return \Helper::isVideo($src);
	}

	public function fn_session($key) {
		return $_SESSION[$key];
	}

	public function fn_csrf_input() {
		return isset($_SESSION['CSRF_TOKEN_CODE'])? join(' ', array(
					'<input type="hidden"',
					'name="'. $_SESSION['CSRF_TOKEN_NAME']. '"',
					'value="'. $_SESSION['CSRF_TOKEN_CODE']. '"',
					'/>'
				)): '';
	}

	public function fn_imageSrc($src, $prefix) {
		return \Helper::imageSrc($src, $prefix);
	}
	
	public function ft_dump($var) {
		var_dump($var);
	}

	public function fn_url_path() {
		$dir = dirname($_SERVER['REQUEST_URI']);
		return $dir;
		return substr($dir, strlen(DOC_DIR));
	}
	public function fn_pagination_url($url = null, $pn = 1) {
		$pn    = intval($pn);
		$tmp   = explode('?', $_SERVER['REQUEST_URI']);
		$query = array();
		if(count($tmp) == 2) {
			parse_str(array_pop($tmp), $query);
			unset($query['pn']);
		}
		if($url) {
			$parts = array(
				$url,
				$pn < 1? 1: $pn,
				http_build_query($query)
			);
			$url = rtrim(join('/', $parts), '/');
		} else {
			$query['pn'] = $pn;
			$url = $tmp[0]. '?'. http_build_query($query);
		}
		return $url;
	}

	public function fn_checkPermission($mod, $val) {
		if(is_string($val)) {
			$val = constant('PERM_'. $val);
		}
		return \SwimAdmin::checkPermission($mod, $val, false);
	}
}
