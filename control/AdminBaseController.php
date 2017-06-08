<?php
require 'BaseController.php';

class AdminBaseController extends BaseController {
    protected function logData($kind, $pre, $after, $targetId) {
        $AdminLog = Admin::model('admin.log');
        $change = false;
		if(is_array($pre) && is_array($after)) {
			$preData = array();
			foreach($after as $key => $val) {
				if(isset($pre[$key])) {
					$preData[$key] = $pre[$key];
                    if($val !== $pre[$key]) {
                        $change = true;
                    }
				} else {
                    $change = true;
                }
			}
			$pre = $preData;
		} else if($pre !== $after) {
            $change = true;
        }
        if($change) {
            $logData  = array(
                'AdminID'   => $_SESSION[Config::SESSION_UID],
                'Kind'      => $kind,
                'Pre'       => $pre,
                'After'     => $after,
                'Target'    => $targetId? strval($targetId): null,
                'Timestamp' => time()
            );
            $AdminLog->insert($logData);
        }
    }
}