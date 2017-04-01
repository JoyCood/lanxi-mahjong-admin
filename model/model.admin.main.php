<?php !defined('TANG_FENG') AND exit('Access Denied!');
class ModelAdminMain {
	private $fields = array(
		'username'		=> '',	 	// 用户名
		'password'		=> '', 		// 密码
		'name'			=> '',		// 姓名
		'active'		=> true,	// 状态: true 可用， false 停用
		'last_login'	=> 0,		// 最后登录时间
		'last_ip'		=> '',		// 最后登录IP
		'group'			=> 0		// 所属组
	);


	public function collection() {
		return Admin::db('admin');
	}

	public function addUser($user) {
		$admin 	= $this->collection();
		$user 	= Admin::combine($this->fields, $user);
		$user = Helper::allowed($user, array_keys($this->fields));
		$admin->insert($user);

		$user['id'] = (string)$user['_id'];
		unset($user['_id']);

		return $user;
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

	public function changePassword($username, $password) {
		$collection = $this->collection();
		return $collection->update(array('username' => $username), array('$set' => array(
			'password' => $this->password($password)
		)));
	}

	public function getOneByName($username) {
		$collection = $this->collection();
	    $row = $collection->findOne(array('username' => $username));
	    return $row;
	}

	public function getOneById($id) {
		$collection = $this->collection();
	    $row = $collection->findOne(array('_id' => new MongoId($id)));
	    return $row;
	}

	public function pagination($url = '', $pnValue = null) {
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

	/**
	 * 生成加密密码
	 */
	public function password($password) {
		return md5('_tangfeng_'. $password);
	}

	public function getUserByUsername($username) {
		$admin 	= Admin::db('admin');
		$user   = $admin->findOne(array('username' => $username));
		return $user;
	}
}
