<?php !defined('TANG_FENG') AND exit('Access Denied!');

class ModelSequenceMain {
    public function collection() {
	    return Admin::db('sequence');
	}

	public function nextSequence($name, $default=10000) {
        $seq =  $this->collection()->findAndModify(
			        array('_id' => trim($name)),
					array('$inc' => array('seq'=>1)),
					array('seq'=>true),
					array('new' => true)
				);
        if($seq===null) {
			$seq =  $this->collection()->findAndModify(
						array('_id' => trim($name)),
						array('$set' => array('seq'=>$default)),
						array('seq'=>true),
						array('new' => true, 'upsert'=>true)
					);
		}
		return $seq['seq'];
	}
}
