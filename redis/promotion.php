<?php
//特卖数据存储底层

namespace REDIS;

class Promotion extends _Redis {

	var $namespace = 'promotion';
	var $dsn_type = 'database';

	//商品周销量计数器
	function saleCounter($sp, $goods_id, $date = ''){

		if(!$sp || !$goods_id)return;
		$key = "counter:sales:{$sp}:{$goods_id}";
		if(!$date)$date = date('Y-m-d H:i:s');
		$ret = $this->zadd($key, time(), date('ymdHis', strtotime($date)).rand(100,999));
		if($ret){
			$this->expire($key, WEEK);
			return $ret;
		}
	}

	//返回商品周销量
	function getSaleCount($sp, $goods_id, $date = ''){

		if(!$sp || !$goods_id)return;

		$key = "counter:sales:{$sp}:{$goods_id}";

		if(!$date)$date = date('Y-m-d');
		$start = strtotime($date) - WEEK;
		$end = time();

		if(rand(0,10) > 7)//清理
			$this->zremrangebyscore($key, 0, time() - WEEK*4);
		return intval($this->zcount($key, $start, $end));
	}

	//入库商品计数器
	function goodsCounter($sp, $num=0){

		if(!$sp)return;
		$key = "counter:goods:total_num";
		if(!$num){
			return $this->zincrby($key, 1, $sp);
		}else{
			$this->zadd($key, $num, $sp);
		}
		return true;
	}

	//返回指定商城商品数
	function getGoodsCount($sp=''){

		$key = "counter:goods:total_num";
		if($sp){
			return $this->zscore($key, $sp);
		}else{
			return $this->zrangebyscore($key, 0, '+inf', array('withscores'=>true));
		}
	}

	//特卖商品计数器
	function promoCounter($sp, $goods_id, $num=0){

		if(!$sp || !$goods_id)return;
		$key_sp = "counter:promo:sp:total_num";
		$key_cat = "counter:promo:cat:total_num";
		$key_today_sp = $key_sp.":date:".date('Ymd');
		$key_today_cat = $key_cat.":date:".date('Ymd');
		if(!$num){
			//每日各商城特卖数
			$this->zincrby($key_today_sp, 1, $sp);
			$this->expire($key_today_sp, WEEK);

			//每日各分类特卖数
			$detail = D('promotion')->goodsDetail($sp, $goods_id);

			if(@$detail['cat']){
				foreach($detail['cat'] as $cat){
					$this->zincrby($key_today_cat, 1, $cat);
					$this->expire($key_today_cat, WEEK);
				}
			}

			//累计各商城特卖数
			return $this->zincrby($key_sp, 1, $sp);
		}else{
			$this->zadd($key_sp, $num, $sp);
		}
		return true;
	}

	//返回指定商城特卖商品数
	function getPromoSpCount($sp=''){

		$key = "counter:promo:sp:total_num";
		if($sp){
			return $this->zscore($key, $sp);
		}else{
			return $this->zrangebyscore($key, 0, '+inf', array('withscores'=>true));
		}
	}

	//返回指定日期分类特卖商品数
	function getPromoCatCountDate($cat='', $date=''){

		if(!$date)$date = date('Ymd');
		$key = "counter:promo:cat:total_num:date:".$date;
		if($cat){
			return $this->zscore($key, $cat);
		}else{
			return $this->zrangebyscore($key, 0, '+inf', array('withscores'=>true));
		}
	}

	//返回指定日期商城特卖商品数
	function getPromoSpCountDate($sp='', $date=''){

		if(!$date)$date = date('Ymd');
		$key = "counter:promo:sp:total_num:date:".$date;
		if($sp){
			return $this->zscore($key, $sp);
		}else{
			return $this->zrangebyscore($key, 0, '+inf', array('withscores'=>true));
		}
	}

	//统计商品是否被重复购买，购买人数超过5个才入库，用于减轻商品数
	function repeatBuy($sp, $url_tpl, $url_id){

		if(!$sp || !$url_tpl || !$url_id)return;
		$key = 'repeat:goods:sp:'.$sp.':url_tpl:'.$url_tpl.':url_id:'.$url_id;
		$counter = $this->incr($key);
		$this->expire($key, WEEK);
		if($counter > C('comm', 'promo_import_goods_sales_min'))return true;
	}
}
?>