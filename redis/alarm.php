<?php
//各类报警发送记录底层

namespace REDIS;

class Alarm extends _Redis {

	protected $namespace = 'alarm';

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