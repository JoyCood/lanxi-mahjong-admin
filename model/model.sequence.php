<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelSequence {
    public function collection() {
	    return Admin::db('sequence');
	}

	public function nextSequence($name) {
        $seq =  $this->collection()->findAndModify(
			        array('_id' => trim($name)),
					array('$inc' => array('seq'=>1)),
					array('seq'=>true),
					array('new' => true, 'upsert'=>true)
		);	
		return $seq['seq'];
	}
}
