<?php
require(DOC_ROOT. '/control/BaseController.php');

class SystemController extends BaseController {
    //一些开关设置
    public function toggleAction() {
		$num = intval($this->request->post('release-num', 0));
        $data = array(
            'broadcast'   => Config::BROADCAST_ENABLED,
            'phone-login' => Config::PHONE_LOGIN_ENABLED,
            'phone-reg'   => Config::PHONE_REG_ENABLED,
            'apple-pay'   => ($num>0 && $num>=Config::RELEASE_NUMBER),
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
			'乡亲们，如发现游戏bug或建议请联系微信号：lanxiqueshen168',
			'拉上你的好友一起来玩吧'
		);
		$this->responseJSON($data);
	}    

    //检测版本更新
    public function versionCheckAction() {
        $os = trim($this->request->post('os'));
        $version = trim($this->request->post('version'));

        if(!in_array($os, array('android', 'ios')) || !$version) {
            $response = array(
                'errcode' => '10000',
                'errmsg'  => '非法请求'
            );
            $this->responseJSON($response);
        }
        $release = Config::get('release', $os);  
        list($v1, $v2, $v3) = explode('.', $version);
        list($r1, $r2, $r3) = explode('.', $release['version']);

        $v1 = intval($v1);
        $v2 = intval($v2);
        $v3 = intval($v3);
        $r1 = intval($r1);
        $r2 = intval($r2);
        $r3 = intval($r3);

        $update = 0;
        if($v1<$r1) {
            $update = 2;
        } else if($v2<$r2 && $v1==$r1) {
            $update = 1;
        } else if($v3<$r3) {
            $update = 1;
        }
        $release['update'] = $update;
        $this->responseJSON($release);
    }
}
