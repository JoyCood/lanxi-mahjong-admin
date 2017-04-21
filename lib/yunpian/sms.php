<?php !defined('TANG_FENG') AND exit('Access Denied!');

class YunPianSms
{
    const API = "http://yunpian.com/v1/sms/send.json";
    
    public function send($msg)
    {
        $data = "";
        $info=parse_url(self::API);
        $fp=fsockopen($info["host"],80,$errno,$errstr,30);
        if(!$fp){
            return $data;
        }
        $head="POST ".$info['path']." HTTP/1.0\r\n";
        $head.="Host: ".$info['host']."\r\n";
        $head.="Referer: http://".$info['host'].$info['path']."\r\n";
        $head.="Content-type: application/x-www-form-urlencoded\r\n";
        $head.="Content-Length: ".strlen(trim($msg))."\r\n";
        $head.="\r\n";
        $head.=trim($msg);
        $write=fputs($fp,$head);
        $header = "";
        while ($str = trim(fgets($fp,4096))) {
            $header.=$str;
        }
        while (!feof($fp)) {
            $data .= fgets($fp,4096);
        }

        return $data;
    }

}
