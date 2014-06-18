<?php
//DAL:xcache本地缓存层

namespace XCACHE;

class _Xcache extends \Object {

	function get($key){
		return xcache_get($key);
	}

	function set($key, $value, $ttl=3600){
		if(!$ttl)return;
		xcache_set($key, $value, $ttl);
	}

	function exist($key){
		return xcache_isset($key);
	}

	function delete($key){
		xcache_unset($key);
	}

	function del($key){
		xcache_unset($key);
	}

	function unset_prefix($key_prefix){
		xcache_unset_by_prefix($key_prefix);
	}

	function inc($key, $step=1, $ttl=3600){
		if(!$ttl)return;
		return xcache_inc($key, $step, $ttl);
	}

	function dec($key, $step=1, $ttl=3600){
		if(!$ttl)return;
		return xcache_dec($key, $step, $ttl);
	}

	function lock($key){
		$fp = fopen("/tmp/{$key}.lock", "w");
		return flock($fp, LOCK_EX);
	}

	function unlock(){
		flock($fp, LOCK_UN);
	}
}

?>