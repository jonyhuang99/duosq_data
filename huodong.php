<?php
//DAL:活动模块
namespace DAL;

class Huodong extends _Dal {

	//新增活动数据
	function add($data){

		if(!$data)return;
		return $this->db('huodong')->add($data);
	}

	//获取活动数据
	function get($type, $device_id, $channel){
		if(!$type || !$device_id || !$channel){
			return false;
		}

		$ret = $this->db('huodong')->find(array('type'=>$type, 'account'=>$device_id, 'channel'=>$channel));
		return clearTableName($ret);
	}
}
?>