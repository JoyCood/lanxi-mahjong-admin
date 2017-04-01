<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelNoticeMain
{
	private $fields = array(
		'Title',   #string  公告标题
        'Content', #string  公告内容
		'Expire',  #uint32  过期时间
		'Time',    #uint32  创建时间
		'AdminId'  #string  管理员id
	);

	public function collection() {
	    return Admin::db('agent_agent_notice');
	}

	//插入
    public function insert($data) {
	    $collection = $this->collection();
		$data = Helper::allowed($data, $this->fields);
		$result = $collection->insert($data);
        return $data;
	}

	//更新
	public function update($data, $id) {
	    $collection = $this->collection();
		$id = new MongoId($id);
		$data = Helper::allowed($data, $this->fields);
		$result = $collection->update(
				array('_id' => $id), 
				array('$set' => $data)
		);

		return $result;
	}

	//查找
	public function find($filters=array(), $projection=array()) {
		$filters = (array)$filters;
		$projection = (array)$projection;
	    $collection = $this->collection();
		return $collection->find($params, $projection);
	}

	//查找一条数据
	public function findOne($filters=array(), $projection=array()) {
	    $filters = (array)$filters;
		$projection = (array)$projection;
		return $this->collection()->findOne($filters, $projection);
	}

	public function deleteById($ids) {
	    $ids = (array)$ids;
		$size = sizeof($ids);
		for($idx=0; $idx<$size; $idx++){
		    $ids[$idx] = new MongoId($ids[$idx]);
		}
		return $this->collection()->remove(array(
		    '_id' => array('$in' => $ids)
		));
	}

	//分页
    public function pagination($url='', $pnValue=null) {
        $params = Helper::parseQueryString($url? $url: $_SERVER['REQUEST_URI']);
	    $pn     = Helper::popValue($params, 'pn', 1);	
		$sort   = Helper::popValue($params, 'sort', 'Time');
		$order  = Helper::popValue($params, 'order', -1);
        
	    $filters = array();	
		$data = Admin::pagination(
			$url, 
			$this->collection(),
	        is_null($pnValue)? $pn : $pnValue,
	        $filters,
	        array($sort => intval($order) > 0? 1: -1)
	    );

		return $data;
	}

}
