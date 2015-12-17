<?php
//DAL:缓存控制模块
namespace DAL;

class Cache extends _Dal {

	//获取缓存
	function get($key){

		if(CACHE_DATA && !@$_REQUEST['DISABLE_DATA_CACHE'])
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

	//自增类缓存，支持有效期延续
	function incr($key, $expire=3600){
		$ret = $this->redis('cache')->incr($key);
		$this->redis('cache')->expire($key, $expire);
		return $ret;
	}

	//有效期延续
	function expire($key, $expire=3600){
		return $this->redis('cache')->expire($key, $expire);
	}

	//获取文本缓存
	function getFile($key){

		if(CACHE_DATA && !DEBUG)
			return $this->redis('cache')->get($key);
		else
			return null;
	}

	//设置文本缓存
	function setFile($key, $data, $expire=3600){

		if(!$key || !$data)return;
		return $this->redis('cache')->set($key, $data, $expire);
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