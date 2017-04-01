<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelAdminLog
{
    private $fields = array(
		'AdminID',       #string  操作人id  
		'Kind',          #uint32  操作类型
		'WindgetID',     #uint32  道具类型
		'Count',         #int32   操作资源数量，可为负值
		'Pre',           #string  修改前的值
		'After',         #string  修改后的值
		'Target',        #string  目标管理员id或玩家id
		'Desc',          #string  不知道是什么
		'Param',         #?       不知道是什么类型、作何用
		'Timestamp'      #uint32  系统时间戳
	);

	public function collection() {
	    return Admin::db('admin_log');
	}

	public function insert($data) {
		$data = Helper::allowed($data, $this->fields);
		$collection = $this->collection();
		return $collection->insert($data);
	}

    public function find($filters=array(), $projection=array()) {
	    $filters = (array)$filters;
		$projection = (array)$projection;
		$collection = $this->collection();
		return $collection->find($filters, $projection);
	}

	public function findOne($filters=array(), $projection=array()) {
	    $filters = (array)$filters;
		$projection = (array)$projection;
		$collection = $this->collection();
		return $this->collection()->findOne($filters, $projection);
	}

	public function update($data, $id) {
	    $collection = $this->collection();
		$data = Helper::allowed($data, $this->fields);
		$result = $collection->update(
				array('_id' => new MongoId($id)),
				array('$set' => $data)
		);
	}

    public function pagination($url='', $pnValue=null) {
        $params = Helper::parseQueryString($url? $url: $_SERVER['REQUEST_URI']);
	    $pn = Helper::popValue($params, 'pn', 1);
	    $sort = Helper::popValue($params, 'sort', 'Timestamp');
	    $order = Helper::popValue($params, 'order', -1);

	    $filters = array();
	    $data = Admin::pagination(
		    $url, 
			$this->collection(),
			is_null($pnValue)? $pn: $pnValue,
			$filters,
			array($sort=>intval($order) > 0? 1: -1)
		);	

		return $data;
	}
}
