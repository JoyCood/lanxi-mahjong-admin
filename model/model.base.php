<?php !defined('TANG_FENG') AND exit('Access Denied!');
class ModelBase {
    private $collection = 'admin';
	private $fields     = array(

	);


	public function collection() {
		return Admin::db($this->collection);
	}

	public function update($data, $id) {
        $collection = $this->collection();
        $id = new MongoId($id);
        $data = Helper::allowed($data, array_keys($this->fields));
        $data['modified_by'] = $_SESSION[S_USER];
        $data['modified_on'] = time();
        $result = $collection->update(array('_id' => $id), array('$set' => $data));
        return $result;
    }

	public function deleteById($ids) {
        $ids = (array)$ids;
        for($idx=0, $l=sizeof($ids); $idx<$l; $idx++) {
            $ids[$idx] = new MongoId($ids[$idx]);
        }
        $collection = $this->collection();
        $result = $collection->remove(array(
            '_id' => array('$in' => $ids)
        ));
        return $result;
    }

	public function getOneById($id) {
		$collection = $this->collection();
	    $row = $collection->findOne(array('_id' => new MongoId($id)));
	    return $row;
	}

	public function pagination($url = '', $filters = array(), $pnValue = null) {
        $params  = Helper::parseQueryString($url? $url: $_SERVER['REQUEST_URI']);
        $pn      = Helper::popValue($params, 'pn', 1);
        $sort    = Helper::popValue($params, 'sort', 'username');
        $order   = Helper::popValue($params, 'order', 1);
        $filters = array(
        	'username' => array('$ne' => 'admin')  // admin 隐藏
        );
        $data = Admin::pagination(
            $url,
            $this->collection(),
            is_null($pnValue)? $pn: $pnValue,
            $filters,
            array('active' => -1, $sort => intval($order) > 0? 1: -1)
        );
        $data = $this->combineGroupName($data);
        return $data;
    }

    protected function combineGroupName($data) {
    	$items = array();
    	$gids  = array();
    	foreach($data['items'] as $item) {
    		if(isset($item['group']) && is_array($item['group'])) {
    			foreach($item['group'] as $gid) {
    				$gid = trim($gid);
    				if($gid) {
    					$gids[] = new MongoId($gid);
    				}
    			}
    		}
    	}

    	$groups = array();
    	if($gids) {
    		$group  = Admin::db('group');
    		$result = $group->find(array(
    			'_id' => array(
    				'$in' => $gids
    			)
    		));
    		foreach($result as $item) {
    			$gid = (string)$item['_id'];
    			$groups[$gid] = $item['name'];
    		}
    	}

    	foreach($data['items'] as $item) {
    		$item['groups'] = array();
    		if(isset($item['group']) && is_array($item['group'])) {
    			foreach($item['group'] as $gid) {
    				if(isset($groups[$gid])) {
    					$item['groups'][] = $groups[$gid];
    				}
    			}
    		}
    		$items[] = $item;
    	}
    	$data['items'] = $items;

    	return $data;
    }
}
