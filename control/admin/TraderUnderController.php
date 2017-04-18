<?php
require('TraderController.php');

class TraderUnderController extends TraderController{
    protected $under = true;
    public function init() {
        $this->addViewData('MENU_ITEM', 'trader_under');
    }
}