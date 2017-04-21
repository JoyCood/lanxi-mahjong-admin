<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelTraderRelate {
    private $fields = array(
        'GameId', #string 玩家或代理商的游戏id
        'Parent', #string 代理商的游戏id
        'Agent',  #uint32 GameId是普通玩家还是代理商，(0普通玩家，1代理商)
        'CTime'   #uint32 绑定的时间
    );

    public function collection() {
        return Admin::db('agent_user'); 
    }

    public function findOne($filter, $projection=array()) {
        return $this->collection()->findOne($filter, $projection);
    } 

    public function find($filter, $projection=array()) {
        return $this->collection()->find($filter, $projection);
    }

    public function update($filter, $data) {
        $data = Helper::allowed($data, $this->fields);
        return $this->collection()->update($filter, array('$set' => $data)); 
    }
}
