<?php
//DAL:缓存控制模块
namespace DAL;

class Cache extends _Dal {

	//获取缓存
	function get($key){

		if(CACHE_DATA)
			return $this->redis('cache')->getArray($key);
		else
			return null;
	}

	//设置缓存，支持存储空值，支持0
	function set($key, $data, $expire=3600, $support_empty=false){

		if($support_empty){
			if(!$key)return;
			if(!$data && $data !== 0)$data = '__empty__';
			if($data === 0)$data = '__zero__';
			return $this->redis('cache')->setArray($key, $data, $expire);
		}else{
			if((!$key || !$data) && $data !== 0)return;
			if($data === 0)$data = '__zero__';
			return $this->redis('cache')->setArray($key, $data, $expire);
		}
	}

	//判断cache是否空值，进行返回
	function ret($cache, $default=null){

		if($cache == '__empty__'){
			return $default;
		}
		if($cache == '__zero__'){
			return 0;
		}
		return $cache;
	}

	//清除缓存
	function clean($key){
		return $this->redis('cache')->delete($key);
	}
}
?>