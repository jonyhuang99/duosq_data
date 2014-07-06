<?php
//DAL:权重计算模块
//TODO权重计算改为不用重启进程即可生效，目前要重启import_goods和update_weight进程
namespace DAL;

class Weight extends _Dal {

	//默认算法
	var $default_exp = 'e140703';

	function getExp(){
		return $this->default_exp;
	}

	/**
	 * 更新指定促销商品的权重
	 * @param  [type] $sp       [description]
	 * @param  [type] $goods_id [description]
	 * @param  string $exp      输入指定的权重算法，输出算法运算信息
	 * @return [type]           [description]
	 */
	function update($sp, $goods_id, &$exp = ''){

		if($exp && method_exists(this, $exp)){
			$e = $exp;
		}else{
			$e = $this->default_exp;
		}

		$promo_detail = D('promotion')->promoDetail($sp, $goods_id);
		if(!$promo_detail)return;

		$exp_detail = '';
		$weight = $this->$e($promo_detail, $exp_detail);

		$exp = $exp_detail = "[{$e}]".$exp_detail;
		return $this->db('promotion.queue_promo2cat')->update($sp, $goods_id, array('weight'=>$weight,'weight_exp'=>$e,'weight_detail'=>$exp_detail));
	}

	//获取指定特卖商品权重
	function get($sp, $goods_id, &$exp = ''){

		if($exp && method_exists(this, $exp)){
			$e = $exp;
		}else{
			$e = $this->default_exp;
		}

		$promo_detail = D('promotion')->promoDetail($sp, $goods_id);
		if(!$promo_detail)return;

		$exp_detail = '';
		return $this->$e($promo_detail, $exp_detail);
	}

	/**
	 * 非京东B2C权重调高，8折商品与tmall 3折商品权重一样
	 * 越新降价的商品权重越大，8折降价商品高于2天前4折商品
	 * 销量越高权重越大，最高周销量可停留3天
	 */
	function e140703($promo_detail, &$exp_detail){

		$sp = $promo_detail['sp'];
		$goods_id = $promo_detail['goods_id'];

		$weight = array();
		$tmp_detail = array();

		//B2C权重
		if($sp!='taobao' && $sp!='tmall' && $sp!='jd'){
			$weight[] = 1;
			$tmp_detail['sp_weight'] = "{$sp}=>1";
		}

		//折扣权重
		$rate = rate_diff($promo_detail['price_now'], $promo_detail['price_avg']);
		if($rate > 70){
			$weight[] = 5;
			$tmp_detail['discount'] = "{$rate}%=>5";
		}else if($rate > 60 && $rate <= 70){
			$weight[] = 4;
			$tmp_detail['discount'] = "{$rate}%=>4";
		}else if($rate > 40 && $rate <= 60){
			$weight[] = 3;
			$tmp_detail['discount'] = "{$rate}%=>3";
		}else if($rate > 20 && $rate <= 40){
			$weight[] = 2;
			$tmp_detail['discount'] = "{$rate}%=>2";
		}else if($rate <= 20){
			$weight[] = 1;
			$tmp_detail['discount'] = "{$rate}%=>1";
		}

		//日期权重
		$time_diff = strtotime($promo_detail['createdate']) - strtotime('2014-06-01');
		$day_diff = ceil($time_diff/DAY);
		$weight[] = $day_diff * 3;
		$tmp_detail['day'] = "{$day_diff}|{$promo_detail['createdate']}=>".$day_diff*3;

		//销量权重
		$week_sales = $this->redis('promotion')->getSaleCount($sp, $goods_id);
		if(!iSp($sp)){
			if($week_sales <= 20){
				$weight[] = 2;
				$tmp_detail['week_saled'] = "{$week_sales}=>2";
			}else if($week_sales > 20 && $week_sales <= 50){
				$weight[] = 4;
				$tmp_detail['week_saled'] = "{$week_sales}=>4";
			}else if($week_sales > 50 && $week_sales <= 100){
				$weight[] = 6;
				$tmp_detail['week_saled'] = "{$week_sales}=>6";
			}else if($week_sales > 150){
				$weight[] = 8;
				$tmp_detail['week_saled'] = "{$week_sales}=>8";
			}
		}else{
			if($week_sales <= 40){
				$weight[] = 2;
				$tmp_detail['week_saled'] = "{$week_sales}=>2";
			}else if($week_sales > 40 && $week_sales <= 100){
				$weight[] = 4;
				$tmp_detail['week_saled'] = "{$week_sales}=>4";
			}else if($week_sales > 100 && $week_sales <= 200){
				$weight[] = 6;
				$tmp_detail['week_saled'] = "{$week_sales}=>6";
			}else if($week_sales > 300){
				$weight[] = 8;
				$tmp_detail['week_saled'] = "{$week_sales}=>8";
			}
		}

		$ret = 0;
		foreach ($weight as $w) {
			$ret += $w;
		}
		$tmp_detail['weight'] = $ret;
		$exp_detail = buildSet($tmp_detail);

		return $ret;
	}
}
?>