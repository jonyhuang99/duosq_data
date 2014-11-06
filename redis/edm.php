<?php
//DAL:EDM模块
namespace REDIS;

class Edm extends _Redis {

	protected $namespace = 'edm';
	protected $mcache = 300;

	//标识用户被发过的EDM
	function markSentId($account, $channel, $edm_id){
		if(!$account|| !$channel || !$edm_id)return;
		$ret = $this->sadd('sent_id:channel:'.$channel.':account:'.$account, $edm_id);
		$this->expire('sent_id:channel:'.$channel.':account:'.$account, WEEK*2);
		return $ret;
	}

	//标识用户被发过的EDM
	function getSentId($account, $channel){
		if(!$account|| !$channel)return;
		return $this->smembers('sent_id:channel:'.$channel.':account:'.$account);
	}

	//删除用户被发过的ID记录
	function delSentId($account, $channel, $edm_id){
		if(!$account|| !$channel || !$edm_id)return;
		$ret = $this->srem('sent_id:channel:'.$channel.':account:'.$account, $edm_id);
		return $ret;
	}
}
?>