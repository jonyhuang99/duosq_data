<?php
//DAL:缓存控制模块
namespace DAL;

class Cache extends _Dal {

	function get($key){

		return $this->redis('cache')->getJson($key);
	}

	function set($key, $data, $expire=3600){

		if(!$key || !$data)return;
		return $this->redis('cache')->setJson($key, $data, $expire);
	}
}
?>