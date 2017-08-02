<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelAuthMain
{
	const CHANNEL_PHONE = 1;
	const CHANNEL_WEIXIN = 2;
    const CHANNEL_MP = 3;

    const AUTHCODE_EXPIRE = 300; //验证码过期时间    

    private $fields = array(
		'Channel',       #uint32
        'Phone',         #string
        'Code',          #string
        'CTime',         #uint32
		'Openid',        #string 
		'Unionid',       #string
		'Access_token',  #string
		'Refresh_token', #string
        'NonceStr',      #string
        'Jsapi_ticket',  #string
    );

    public function collection() {
        return Admin::db('authorize');
    }

    public function findOne($filter, $projection=array()) {
        return $this->collection()->findOne($filter, $projection);    
    }

    public function insert($data) {
        $data = Helper::allowed($data, $this->fields);
        return $this->collection()->insert($data);
    }

    public function update($filter, $data) {
        $data = Helper::allowed($data, $this->fields);
        return $this->collection()->update($filter, array('$set' => $data));
    }

	public function findAndModify($filter, $data, $projection=null, $options=array('new'=>true)) {
	
		$data = array('$set' => $data);
		return $this->collection()->findAndModify($filter, $data, $projection, $options);
	}

}
