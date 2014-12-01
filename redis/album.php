<?php
//知会任务数据存储底层
namespace REDIS;

class Album extends _Redis {

	var $namespace = 'album';

	//标记该专辑本次session已读
	function markReaded($account, $channel, $album_ids){

		if(!$account || !$channel || !$album_ids)return;
		$key = 'readed:account:'.$account.':channel:'.$channel;

		foreach($album_ids as $album_id){
			$this->hset($key, $album_id, 1);
		}

		$this->expire($key, HOUR*4);
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
	function checkReaded($account, $channel, $album_id){

		if(!$account || !$channel || !$album_id)return;
		$key = 'readed:account:'.$account.':channel:'.$channel;
		return $this->hget($key, $album_id);
	}

	//清除已读记录
	function clearReaded($account, $channel){
		if(!$account || !$channel)return;
		$key = 'readed:account:'.$account.':channel:'.$channel;
		return $this->del($key);
	}
}
?>