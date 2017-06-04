<?php 
require(DOC_ROOT. '/control/BaseController.php');

class NoticeController extends BaseController {
    public function broadcastAction() {
	    $filters = array();
		$projection = array('_id'=>0, 'Content'=>1);
        $cursor = Admin::model('notice.main')->find($filters, $projection);
		$data = array();
		foreach($cursor as $item) {
		    $data[] = $item['Content'];
		}
		$this->responseJSON($data);
	}    
}
