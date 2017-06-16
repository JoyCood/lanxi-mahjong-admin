<?php
require(DOC_ROOT. '/control/BaseController.php');

class SystemController extends BaseController {
    //一些开关设置
    public function toggleAction() {
        $data = array(
            'broadcast'   => Config::BROADCAST_ENABLED,
            'phone-login' => Config::PHONE_LOGIN_ENABLED,
            'phone-reg'   => Config::PHONE_REG_ENABLED,
            'apple-pay'   => Config::APPLE_PAY_ENABLED,
            'bind-trader' => Config::BIND_TRADER_ENABLE,
        );
        $this->responseJSON($data);
    }

    //系统公告跑马灯
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
