<?php
//特卖数据存储底层

namespace REDIS;

class Promotion extends _Redis {

	var $namespace = 'promotion';
	var $dsn_type = 'database';

	//商品周销量计数器
	function saleCounter($sp, $goods_id){

		$key = 'sale_counter:week:'.date('W');
		$ret = $this->hincrby($key, $sp . '|' . $goods_id, 1);
		if($ret){
			$this->expire($key, WEEK * 2);
			return $ret;
		}
	}

	//返回商品周销量
	function getSaleCount($sp, $goods_id, $week = ''){

		if(!$week)$week = date('W');
		$key = 'sale_counter:week:'.$week;
		return $this->hget($key, $sp . '|' . $goods_id);
	}

	//统计商品是否被重复购买，购买人数超过5个才入库，用于减轻商品数
	function repeatBuy($sp, $url_tpl, $url_id){

		if(!$sp || !$url_tpl || !$url_id)return;
		$key = 'repeat:goods:sp:'.$sp.':url_tpl:'.$url_tpl.':url_id:'.$url_id;
		$counter = $this->incr($key);
		$this->expire($key, WEEK);
		if($counter > 5)return true;
	}
}
?>