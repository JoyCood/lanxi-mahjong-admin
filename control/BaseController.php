<?php
class BaseController {
    protected $app;
    protected $request;
    protected $response;
    
    public function __construct($app) {
        $this->app      = & $app;
        $this->request  = & $app->request;
        $this->response = & $app->response;
    }

    protected function render($view, $data = array()) {
        $this->app->render($view, $data);
    }

    protected function renderJSON($data) {
        $this->app->view->renderJSON($data);
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