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
		$data = array(
			'亲们，如发现游戏bug或建议请联系微信号：70612568',
			'拉上你的好友一起来玩吧'
		);
		$this->responseJSON($data);

	}    
}
