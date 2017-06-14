<?php
class BaseController {
    protected $app;
    protected $request;
    protected $response;
    protected $viewData = array();
    
    public function __construct($app) {
        $this->app      = & $app;
		$this->log      = & $app->log;
        $this->request  = & $app->request;
        $this->response = & $app->response;

        if(Config::get('settings', 'csrfProtection') === 'auto') {
            if($this->request->isPost()) {
                $this->checkCsrfToken();
            } else {
                $this->setCsrfToken();
            }
        }

        $this->init();
    }

    protected function init() {
    }

    protected function render($view, $data = array()) {
        $this->app->render($view, array_merge(
            $this->viewData,
            $data
        ));
    }

    protected function renderJSON($data) {
        if(!is_array($data)) {
            $data = array('result' => (boolean)$data);
        }
        $this->app->view->renderJSON(array_merge(
            $this->viewData,
            $data
        ));
    }

	protected function responseJSON($data,$exit=TRUE) {
		if(!is_array($data)) {
			$data = json_decode($data, true);
		}	
		$this->app->view->renderJSON(array_merge(
			$this->viewData,
			$data
		));
		if($exit) {
		    exit();
		}
	}

    protected function addViewData($key, $val) {
        $this->viewData[$key] = $val;
    }

    protected function error($message, $code = '10000') {
        $error = array(
            'code' 		=> $code,
            'message' 	=> $message
        );

        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        header('APP-CODE:'. $code);
        header('APP-ERROR:'. json_encode($message));
        $this->render('error.html', $error);
        // if($this->request->isAjax()) {
        //     $this->renderJSON($error);
        // } else {
        //     $this->render('error.html', $error);
        // }
        exit();
    }

    protected function logData($kind, $pre, $after, $targetId) {
    }

    protected function setCsrfToken($force = false) {
        if(Config::get('settings', 'csrfProtection')) {
            if($force || empty($_SESSION['CSRF_TOKEN_CODE'])) {
                $prefix = 'LX';
                $secret = Config::get('settings', 'sessionSecret');
                $secret = $secret? $secret: $prefix;
                $_SESSION['CSRF_TOKEN_NAME'] = $prefix. rand(1000, 9999);
                $_SESSION['CSRF_TOKEN_CODE'] = md5($secret. uniqid(mt_rand(), true));
            }
        }
    }

    protected function checkCsrfToken() {
        if(Config::get('settings', 'csrfProtection')) {
            if($this->request->isPost()) {
                $result = false;
                $name   = $_SESSION['CSRF_TOKEN_NAME'];
                $code   = $_SESSION['CSRF_TOKEN_CODE'];

                if($name && $code) {
                    $postToken = $this->request->post($name);
                    if($postToken == $code) {
                        $result = true;
                    }
                }
                $this->setCsrfToken(true);

                if(!$result) {
                    $this->error('令牌已失效，请刷新后再试');
                }
            }
        }
    }
}
