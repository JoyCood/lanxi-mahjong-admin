<?php
require(DOC_ROOT. '/control/BaseController.php');

class GoodsController extends BaseController {

	public function goodsAction() {
		$_SESSION['nick'] = 'huangguoxing';
        var_dump($_SESSION);   	
	} 

	public function placeOrderAction() {
        var_dump($_SESSION);	
	}

}
