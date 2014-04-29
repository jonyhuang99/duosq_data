<?php
//DAL:缓存控制模块
namespace DAL;

class Cache extends _Dal {

	//获取缓存
	function get($key){

		return $this->redis('cache')->getJson($key);
	}

	//设置缓存，支持存储空值
	function set($key, $data, $expire=3600, $support_empty=false){

		if($support_empty){
			if(!$key)return;
			if(!$data && $data !== 0)$data = '__empty__';
			return $this->redis('cache')->setJson($key, $data, $expire);
		}else{
			if((!$key || !$data) && $data !== 0)return;
			return $this->redis('cache')->setJson($key, $data, $expire);
		}
	}

	//判断cache是否空值，进行返回
	function ret($cache, $default=null){

		if($cache == '__empty__'){
			return $default;
		}
		return $cache;
	}
}
?>