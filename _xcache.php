<?php

class AppModel extends Model {

	function off() {
		$this->belongsTo = array();
		$this->hasMany = array();
		$this->hasOne = array();
	}

	//支持数组方式进行批量更新
	function updateStatus($ids, $status, $reason=null) {

		if(!$ids)return;

		if(is_array($ids)){
			foreach($ids as $id){
				if(!$id)continue;
				if($reason!==null)
					$this->save(array($this->primaryKey=>$id, 'status'=>$status, 'reason'=>$reason));
				else
					$this->save(array($this->primaryKey=>$id, 'status'=>$status));
			}
		}else{
			if($status == $this->field('status', array($this->primaryKey=>$ids))) return;
			if($reason!==null)
				$this->save(array($this->primaryKey=>$ids, 'status'=>$status, 'reason'=>$reason));
			else
				$this->save(array($this->primaryKey=>$ids, 'status'=>$status));
		}
		return true;
	}

}

?>