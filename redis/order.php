<?php
//主订单相关操作底层

namespace REDIS;

class Order extends _Redis {

	protected $namespace = 'order';
	protected $dsn_type = 'database';
	/**
	 * 生成20位主订单号，格式[Ymd-His-4位incr]，每秒支持1万订单
	 * @return [string] [主订单号]
	 */
	function createId() {

		$date = date('Ymd-His');
		$key = 'id:' . $date;
		$current = $this->incr($key);
		if(!$current){
			throw new \Exception("redis incr error", 1);
		}
		$this->expire($key, MINUTE);
		return $date . '-' . pad($current, 4);
	}
}
?>