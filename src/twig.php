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
			'fb_status'            => new \Twig_Filter_Method($this, 'ft_fb_status', array('is_safe' => array('html'))),
			'zerofill'             => new \Twig_Filter_Method($this, 'ft_zerofill'),
			'dump'                 => new \Twig_Filter_Method($this, 'ft_dump', array('is_safe' => array('html')))
		);
	}

	public function getFunctions() {
		return array(
			// 'json'  => new Twig_Function_Method($this, 'json_encode')
			'is_video' 	 => new \Twig_Function_Method($this, 'fn_isVideo'),
			'image_src'  => new \Twig_Function_Method($this, 'fn_imageSrc'),
			'check_perm' => new \Twig_Function_Method($this,  'fn_checkPermission'),
			'session'    => new \Twig_Function_Method($this,  'fn_session')

		);
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

	public function ft_fb_status($value) {
		switch($value) {
			case '0':
				$value = '-';
				break;
			default:
				break;
		}
		return $value;
	}

	public function fn_isVideo($src) {
		return \Helper::isVideo($src);
	}

	public function fn_session($key) {
		return $_SESSION[$key];
	}

	public function fn_imageSrc($src, $prefix) {
		return \Helper::imageSrc($src, $prefix);
	}
	
	public function ft_dump($var) {
		var_dump($var);
	}

	public function fn_checkPermission($mod, $val) {
		if(is_string($val)) {
			$val = constant('PERM_'. $val);
		}
		return \SwimAdmin::checkPermission($mod, $val, false);
	}
}
