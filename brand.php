<?php
//DAL:品牌管理模块
namespace DAL;

class Brand extends _Dal {

	//搜索指定分类下特卖的品牌
	function searchInPromo($cat, $subcat=null, $sp_cond=array(), $limit=20){

		if(!$cat)return;

		$cond = array();
		if($cat){
			$cond['cat'] = $cat;
		}

		if($subcat){
			$cond['subcat'] = $subcat;
		}

		$cond = $cond + $sp_cond;
		$cond = arrayClean($cond);

		$cond['brand_id'] = '<> 0';
		$brands = $this->db('promotion.queue_promo2cat')->findAll($cond, 'DISTINCT brand_id', 'weight ASC', 300);
		if(!$brands)return;
		$brands = clearTableName($brands);
		$brand_ids = array();
		foreach ($brands as $brand) {
			$brand_ids[] = $brand['brand_id'];
		}

		if(!$brand_ids)return;
		$brands = $this->db('promotion.brand')->findAll(array('id'=>$brand_ids, 'cat'=>"like %{$cat}%"), 'id,name,name_en,weight', 'weight ASC', $limit);
		$brands = clearTableName($brands);
		return $brands;
	}

	//搜索品牌库
	function search($name, $limit=10){

		if(!$name)return;
		$brands = $this->db('promotion.brand')->findAll(array('name_search' => "like %{$name}%"), 'id,name,name_en,weight', 'weight ASC', $limit);
		return clearTableName($brands);
	}

	//获取品牌详情
	function detail($id, $field=''){

		if(!$id)return;
		$key = 'brand:detail:'.$id;
		$cache = D('cache')->get($key);
		if($cache){
			$detail = D('cache')->ret($cache);
		}else{
			$detail = $this->db('promotion.brand')->find(array('id'=>$id));
			$detail = clearTableName($detail);
			D('cache')->set($key, $detail, MINUTE*10, true);
		}

		if($field)
			return $detail[$field];
		else
			return $detail;
	}

	//匹配品牌
	function matchAndUpdateBrand($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$detail = D('promotion')->goodsDetail($sp, $goods_id);
		//人工审核后不再匹配
		if(!$detail['cat'] || $detail['brand_review'])return;

		$key = 'brand:details:cat:'.md5(serialize($detail['cat']));
		$cache = D('cache')->get($key);
		if($cache){
			$brands = D('cache')->ret($cache);
		}else{
			$brands = $this->db('promotion.brand')->findAll(array('cat'=>"regexp (".join('|',$detail['cat']).')'), 'id,name,name_en,sp_rule,ex_rule');
			D('cache')->set($key, $brands, MINUTE*5);
		}

		$brands = clearTableName($brands);
		$brand_hit = false;
		foreach($brands as $brand){

			if($brand['name'] && preg_match("/{$brand['name']}/i", $detail['name']) && (!$brand['ex_rule'] || !preg_match("/({$brand['ex_rule']})/i", $detail['name']))){
				$brand_hit = $brand;
				break;
			}else if($brand['name_en'] && preg_match("/{$brand['name_en']}[^a-z0-9\+\·\'\’\:\-\&]/i", $detail['name']) && (!$brand['ex_rule'] || !preg_match("/({$brand['ex_rule']})/i", $detail['name']))){
				$brand_hit = $brand;
				break;
			}else if($brand['sp_rule'] && preg_match("/({$brand['sp_rule']})[^a-z0-9\+\·\'\’\:\-\&]/i", $detail['name']) && (!$brand['ex_rule'] || !preg_match("/({$brand['ex_rule']})/i", $detail['name']))){
				$brand_hit = $brand;
				break;
			}
		}

		if($brand_hit){
			$this->db('promotion.goods')->update($sp, $goods_id, array('brand_id'=>$brand_hit['id']));
			$this->db('promotion.queue_promo2cat')->update($sp, $goods_id, array('brand_id'=>$brand_hit['id']));
			$this->db('promotion.review_brand')->add($sp, $goods_id);
			//加入待审核列表
			return $brand_hit;
		}else{

			$this->db('promotion.goods')->update($sp, $goods_id, array('brand_id'=>0));
			$this->db('promotion.queue_promo2cat')->update($sp, $goods_id, array('brand_id'=>0));
		}
	}

	//获取品牌统计
	function getStat(){

		$num_week = $this->db('promotion.queue_promo2cat')->query("SELECT count(DISTINCT brand_id) nu FROM queue_promo2cat WHERE createtime > '".date('Y-m-d', time()-WEEK)."'");
		$num_week = @$num_week[0][0]['nu'];
		return array('num_week'=>intval($num_week));
	}
}
?>