<?php
class BaseController {
    protected $app;
    protected $request;
    protected $response;
    protected $viewData = array();
    
    public function __construct($app) {
        $this->app      = & $app;
        $this->request  = & $app->request;
        $this->response = & $app->response;

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
        if(is_bool($data)) {
            $data = array('result' => $data);
        }
        $this->app->view->renderJSON(array_merge(
            $this->viewData,
            $data
        ));
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
		header('APP-ERROR:'. json_encode($error));

		if($this->request->isAjax()) {
			$this->renderJSON($error);
		} else {
			$this->render('error.html', $error);
		}
		exit();
    }
}