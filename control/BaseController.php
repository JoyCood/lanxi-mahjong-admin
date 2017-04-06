<?php
class BaseController {
    protected $app;
    public function __construct($app) {
        $this->app = $app;
    }

    protected function render($view, $data = array()) {
        $this->app->render($view, $data);
    }
}