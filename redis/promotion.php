<?php
//特卖数据存储底层

namespace REDIS;

class Promotion extends _Redis {

	var $namespace = 'promotion';
	var $dsn_type = 'database';

	//商品当天销量计数器
	function saleCounter($goods_id){

		$key = 'sale_counter:date:'.date('Ymd');
		$ret = $this->hincrby($key, $goods_id, 1);
		if($ret){
			$this->expire($key, DAY*3);
			return $ret;
		}
	}
}
?>