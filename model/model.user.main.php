<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelUserMain
{
    private $fields = array(
    
    );  

    public function collection() {
        return Admin::db('user');
    }

    public function findOne($filter, $projection=array()) {
        return $this->collection()->findOne($filter, $projection);
    }

}
