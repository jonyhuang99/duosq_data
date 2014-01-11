<?php
//知会任务数据存储底层

namespace REDIS;

class Notify extends _Redis {

	var $namespace = 'notify';
	var $dsn_type = 'database';

	//TODO该模式每次均清空通知集合，如果人数很多，将导致风险

	/**
	 * 新增用户通知任务
	 * @param  [type] $cashtype [description]
	 * @param  [type] $user_id  [description]
	 * @return [type]           [description]
	 */
	function addJob($type, $user_id, $o_id){

		if(!$type || !$user_id || !$o_id)return;
		$exist = $this->hget('type:'.$type, $user_id);
		if($exist){
			$arr = unserialize($exist);
			$arr[$o_id] = 1;
			$ret = $this->hset('type:'.$type, $user_id, serialize($arr));
		}else{
			$arr = array($o_id=>1);
			$ret = $this->hset('type:'.$type, $user_id, serialize($arr));
		}

		return $ret;
	}

	/**
	 * 获取用户通知任务
	 * @return [type] [description]
	 */
	function getJob($type){

		if(!$type)return;
		$ret = $this->hgetall('type:'.$type);
		if($ret){
			$this->del('type:'.$type);
			foreach($ret as $user_id => $o_id_arr){
				$ret[$user_id] = array_keys(unserialize($o_id_arr));
			}
		}
		return $ret;
	}
}
?>