<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelTraderRelate {
	const NORMAL = 0; //普通玩家
	const TRADER = 1; //代理商

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

	public function pagination($params, $pnValue=null) {
	    $pn    = Helper::popValue($params, 'pn', 1);
		$sort  = Helper::popValue($params, 'sort', 'CTime');
		$order = Helper::popValue($params, 'order', -1);
        
		$filters = Helper::popValue($params, 'filters', array());

		$data = Admin::pagination(
	        $this->collection(),
		    is_null($pnValue)? $pn: $pnValue,
		    $filters,	
			array($sort => intval($order) > 0? 1: -1)
		);

		if($data && $data['items']) {
			$Trader = Admin::model('trader.main');
			$User   = Admin::model('User.main');
			$items  = array();
	        foreach($data['items'] as $index=>$item) {
				if($item['Agent']==self::TRADER){ //代理商
					$filters = array('Gameid' => $item['GameId']);         
					$user = $Trader->findOne($filters);
					if(!$user) {
					    continue;
					}
					$items[] = array(
						'Nickname' => $user['Nickname'],
						'Gameid'   => $user['Gameid'],
						'Agent'    => self::TRADER
					);
				} else { //普通玩家（非代理商）
			        $filters = array('_id' => $item['GameId']);	
					$user = $User->findOne($filters);
					if(!$user) {
						continue;
					}
					$items[] = array(
						'Nickname' => $user['Nickname'],
						'Gameid'   => $user['_id'],
						'Agent'    => self::NORMAL
				    );
				} 
			}	
			$data['items'] = $items;
		}	
		return $data;
	}
}
