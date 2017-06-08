<?php 
require(DOC_ROOT. '/control/BaseController.php');

class CardController extends BaseController {
    //商品列表
	public function listAction() {
        $card = Config::getOptions('card');
        //$encryped = Helper::encodeParams($goods); 
        //$decryped = Helper::decodeParams($encryped);
        $this->renderJSON($card);  
    }

}
