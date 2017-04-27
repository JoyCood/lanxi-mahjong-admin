<?php 
require(DOC_ROOT. '/control/BaseController.php');

class GoodsController extends BaseController {
    //商品列表
    public function listAction() {
        $goods = Config::getOptions('goods');
        $encryped = Helper::encodeParams($goods); 
        $decryped = Helper::decodeParams($encryped);
        $this->renderJSON($goods);  
    }
}
