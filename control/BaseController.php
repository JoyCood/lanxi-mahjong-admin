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
}