<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelAuthCodeMain
{
    const AUTHCODE_EXPIRE = 300; //验证码过期时间    

    private $fields = array(
        'Phone', #string
        'Code',  #string
        'CTime'  #uint32
    );

    public function collection() {
        return Admin::db('authcode');
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
}
