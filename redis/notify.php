<?php
//知会任务数据存储底层

namespace REDIS;

class Notify extends _Redis {

	var $namespace = 'notify';
	var $dsn_type = 'database';

	//TODO该模式每次均清空通知集合，如果人数很多，将导致风险

	/**
	 * 新增用户通知任务
	 * @param  [type] $notifytype [通知类型]
	 * @param  [type] $user_id    [description]
	 * @return [type]             [description]
	 */
	function addJob($notifytype, $sendtype, $user_id, $o_id){

		if(!$notifytype || !$sendtype || !$user_id || !$o_id)return;
		$exist = $this->hget('notifytype:'.$notifytype.':sendtype:'.$sendtype, $user_id);
		if($exist){
			$arr = unserialize($exist);
			$arr[$o_id] = 1;

			$this->hset('notifytype:'.$notifytype.':sendtype:'.$sendtype, $user_id, serialize($arr));
			$ret = true;

		}else{
			$arr = array($o_id=>1);
			$ret = $this->hset('notifytype:'.$notifytype.':sendtype:'.$sendtype, $user_id, serialize($arr));
		}

		return $ret;
	}

	/**
	 * 获取用户通知任务
	 * @return [type] [description]
	 */
	function getJob($notifytype, $sendtype){

		if(!$notifytype || !$sendtype)return;
		$ret = $this->hgetall('notifytype:'.$notifytype.':sendtype:'.$sendtype);
		if($ret){

			//$this->del('notifytype:'.$notifytype.':sendtype:'.$sendtype);
			foreach($ret as $user_id => $o_id_arr){
				$ret[$user_id] = array_keys(unserialize($o_id_arr));
			}
		}
		return $ret;
	}
}
?>