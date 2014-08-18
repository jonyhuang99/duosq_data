<?php
//DAL:数据库访问层

namespace DB;

class _Db extends \Model {

	var $useDbConfig = 'default'; //可以指定不同数据库连接
	var $useTable = null; //指定表名
	var $tablePrefix = null; //指定表前缀
	var $name = null; //指定db model名字

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
					$this->update($id, array('status'=>$status, 'reason'=>$reason));
				else
					$this->update($id, array('status'=>$status));
			}
		}else{
			if($status == $this->field('status', array($this->primaryKey=>$ids))) return;
			if($reason!==null)
				$this->update($ids, array('status'=>$status, 'reason'=>$reason));
			else
				$this->update($ids, array('status'=>$status));
		}
		return true;
	}

}

?>