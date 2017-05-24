<?php
require 'BaseController.php';

class AdminBaseController extends BaseController {
    protected function logData($kind, $pre, $after, $targetId) {
        $AdminLog = Admin::model('admin.log');
        $logData  = array(
            'AdminId'   => $_SESSION[Config::SESSION_UID],
            'Kind'      => $kind,
            'Pre'       => $pre,
            'After'     => $after,
            'Target'    => $targetId? strval($targetId): null,
            'Timestamp' => time()
        );
        $AdminLog->insert($logData);
    }
}