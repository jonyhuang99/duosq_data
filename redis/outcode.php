<?php
//跟单码相关操作底层

namespace REDIS;

class Outcode extends _Redis {

	var $namespace = 'outcode';
	/**
	 * 生成12位跟单码，格式[ymd+6位incr]，每天支持100万跟单码生成，如果超过则需改算法
	 * @return [string] [跟单码]
	 */
	function create() {

		$date = date('ymd');
		$key = 'id::' . $date;
		$current = $this->incr($key);
		$this->expire($key, DAY * 2);
		return $date . pad($current, 6);
	}
}
?>