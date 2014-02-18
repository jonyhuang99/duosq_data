<?php
//各类存于监控信息管理底层

namespace REDIS;

class Monitor extends _Redis {

	protected $namespace = 'monitor';

	/**
	 * 合并重复报警
	 * @param  string  $key       合并的业务
	 * @param  integer $expire    过期时间
	 * @return [type]             array()
	 */
	function sent($key='',$expire=3600){

		if($this->get($key)){
			return true;
		}else{
			$this->set($key, 1, $expire);
			return false;
		}
	}
}
?>