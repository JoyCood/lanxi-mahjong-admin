<?php 
require(DOC_ROOT. '/control/BaseController.php');

class GoodsController extends BaseController {
    //商品列表
    public function listAction() {
        $this->renderJSON(Config::getOptions('goods'));  
    }
}
