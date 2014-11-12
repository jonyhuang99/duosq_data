<?php
//知会任务数据存储底层
namespace REDIS;

class Ablum extends _Redis {

	var $namespace = 'ablum';

	//标记该专辑本次session已读
	function markReaded($account, $channel, $ablum_ids){

		if(!$account || !$channel || !$ablum_ids)return;
		$key = 'readed:account:'.$account.':channel:'.$channel;

		foreach($ablum_ids as $ablum_id){
			$this->hset($key, $ablum_id, 1);
		}

		$this->expire($key, DAY*3);
		return true;
	}

	//获取本次session读过的专辑
	function getReaded($account, $channel){

		if(!$account || !$channel)return;
		$key = 'readed:account:'.$account.':channel:'.$channel;
		$all = $this->hgetall($key);
		if($all)return array_keys($all);
		return array();
	}

	//确认专辑是否本次session已读
	function checkReaded($account, $channel, $ablum_id){

		if(!$account || !$channel || !$ablum_id)return;
		$key = 'readed:account:'.$account.':channel:'.$channel;
		return $this->hget($key, $ablum_id);
	}

	//清除已读记录
	function clearReaded($account, $channel){
		if(!$account || !$channel)return;
		$key = 'readed:account:'.$account.':channel:'.$channel;
		return $this->del($key);
	}
}
?>