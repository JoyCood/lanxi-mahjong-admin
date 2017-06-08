<?php
class ModelAdminMain {
	const DEFAULT_USER  = 'admin';
	const DEFAULT_PASS  = 'Lanxi123!';
	const PASSWORD_SALT = '_tangfeng_';

	private $fields = array(
		'Username',
		'Password',
		'Name',
		'Status',
		'LastLogin',
		'LastIP',
		'Group',
	);


	public function collection() {
		return Admin::db('admin');
	}

	public function addDefaultUser() {
		$user = array(
			'Username' => self::DEFAULT_USER,
			'Password' => $this->password(self::DEFAULT_PASS),
			'Name'     => 'Administrator',
			'Status'   => true,
			'Group'    => '*'
		);
		return $this->addUser($user)? $this->getUserByUsername(self::DEFAULT_USER): false;
	}

	public function addUser($user) {
		$admin = $this->collection();
		$user  = Helper::allowed($user, $this->fields);
		$admin->insert($user);

		$user['id'] = (string)$user['_id'];
		unset($user['_id']);

		return $user;
	}

	public function update($data, $id) {
        $collection = $this->collection();
        $id = new MongoId($id);
        $data = Helper::allowed($data, array_keys($this->fields));
        $data['ModifiedBy'] = $_SESSION[Config::SESSION_USER];
        $data['ModifiedOn'] = time();
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
		return $collection->update(array('Username' => $username), array('$set' => array(
			'Password' => $this->password($password)
		)));
	}

	public function getOneByName($username) {
		$collection = $this->collection();
	    $row = $collection->findOne(array('Username' => $username));
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
        $sort    = Helper::popValue($params, 'sort', 'Username');
        $order   = Helper::popValue($params, 'order', 1);
        $filters = array(
        	'Username' => array('$ne' => self::DEFAULT_USER)  // admin 隐藏
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
    			$groups[$gid] = $item['Name'];
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
		return md5(self::PASSWORD_SALT. $password);
	}

	public function getUserByUsername($username) {
		$admin = Admin::db('admin');
		$user  = $admin->findOne(array('Username' => $username));
		if($user) {
			$user['id'] = (string)$user['_id'];
			unset($user['_id']);
		}
		return $user;
	}
}
