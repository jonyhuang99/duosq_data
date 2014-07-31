<?php
//DAL:特卖搜索模块
namespace DAL;

class Search extends _Dal {

	//生成指定商品的搜索索引
	function buildIndex($sp, $goods_id){

		$goods_detail = D('promotion')->goodsDetail($sp, $goods_id);
		if(!$goods_detail)return;

		$cat = join('|',(array)$goods_detail['cat']);
		$subcat = join('|',(array)$goods_detail['subcat']);

		$promo_detail = D('promotion')->promoDetail($sp, $goods_id);
		if($promo_detail){
			$weight = D('weight')->get($sp, $goods_id);
			return $this->db('promotion.search_promo')->add($sp, $goods_id, trim($cat.'|'.$subcat.'|'.$goods_detail['name'], '|'), $weight);
		}else{
			$weight = $this->redis('promotion')->getSaleCount($sp, $goods_id);
			return $this->db('promotion.search_goods')->add($sp, $goods_id, trim($cat.'|'.$subcat.'|'.$goods_detail['name'], '|'), $weight);
		}
	}

	//搜索特卖
	function promo($keyword, $sp='', $limit = 45){

		$cond = array();
		$cond['sp'] = $sp;
		$cond['name'] = "like %{$keyword}%";
		$cond = arrayClean($cond);
		if(!isset($cond['sp']))$cond['sp'] = '<> taobao';

		$ret = $this->db('promotion.search_promo')->findAll($cond, 'sp, goods_id', 'weight DESC, id DESC', $limit);
		$ret = clearTableName($ret);
		return D('promotion')->renderPromoDetail($ret);
	}

	//搜索商品
	function goods($keyword, $sp='', $exclude=array(), $limit = 21){

		$cond = array();
		$cond['sp'] = $sp;
		if(!isset($cond['sp']))$cond['sp'] = '<> taobao';
		$cond['name'] = "like %{$keyword}%";
		if($exclude){
			$not_in = 'not in ('.join(',', $exclude).')';
		}else{
			$not_in = '';
		}
		$cond['goods_id'] = $not_in;
		$cond = arrayClean($cond);

		$ret = $this->db('promotion.search_goods')->findAll($cond, 'sp, goods_id', 'weight DESC, id DESC', $limit);
		return clearTableName($ret);
	}
}
?>