<?php
//订阅会话，所有option保存成字符串

namespace REDIS;

class Subscribe extends _Redis {

	protected $namespace = 'subscribe';
	protected $dsn_type = 'database';

	//初始化订阅会话
	function create($sess_id){

		if(!$sess_id)return;

		$key = 'sess:'.$sess_id;
		if($this->exists($key)){
			return true;
		}else{
			$this->hset($key, 'init', 1);
			$this->expire($key, DAY*7);
			return true;
		}
	}

	//探测会话是否存在
	function check($sess_id){

		if(!$sess_id)return;
		$key = 'sess:'.$sess_id;
		if($this->exists($key)){
			return true;
		}
	}

	//保存订阅会话配置
	function set($sess_id, $k, $v=null){

		if(!$this->check($sess_id) || !$k)return;
		$key = 'sess:'.$sess_id;
		if(!$v)$v = '';
		$this->hset($key, $k, $v);
		return true;
	}

	//获取订阅会员配置
	function get($sess_id, $k=null){

		if(!$this->check($sess_id))return;
		$key = 'sess:'.$sess_id;
		if(!$k){
			return $this->hgetall($key);
		}else{
			return $this->hget($key, $k);
		}
	}

	//清除订阅会话
	function clean($sess_id){
		if(!$sess_id)return;
		$key = 'sess:'.$sess_id;
		$this->delete($key);
		return;
	}
}
?>