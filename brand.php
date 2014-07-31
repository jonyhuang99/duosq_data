<?php
//DAL:品牌管理模块
namespace DAL;

class Brand extends _Dal {

	//搜索指定分类下特卖的品牌
	//cat支持数组
	function searchInPromo($cat=array(), $subcat=null, $sp_cond=array(), $limit=60){

		if(!$cat)return;

		$cond = array();
		if($cat){
			$cond['cat'] = $cat;
		}

		if($subcat){
			$cond['subcat'] = $subcat;
		}

		$cond = $cond + (array)$sp_cond;
		$cond = arrayClean($cond);

		$cond['brand_id'] = '<> 0';

		$brands = $this->db('promotion.queue_promo2cat')->findAll($cond, 'DISTINCT brand_id', 'weight DESC', 300);
		if(!$brands)return;
		$brands = clearTableName($brands);
		$brand_ids = array();
		foreach ($brands as $brand) {
			$brand_ids[] = $brand['brand_id'];
		}

		if(!$brand_ids)return;

		if(is_array($cat)){
			$cat = '(' . join('|', $cat) . ')';
		}

		$brands = $this->db('promotion.brand')->findAll(array('id'=>$brand_ids, 'cat'=>"REGEXP {$cat}"), 'id,name,name_en,weight', 'weight DESC', $limit);
		$brands = clearTableName($brands);
		return $brands;
	}

	//搜索品牌库
	function search($condition, $limit=10){

		if(!$condition)return;
		$brands = $this->db('promotion.brand')->findAll($condition, 'id,name,name_en,weight', 'weight DESC', $limit);
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
			if($detail['shop_in_b2c'])
				$detail['shop_in_b2c'] = explode(',', $detail['shop_in_b2c']);
			else
				$detail['shop_in_b2c'] = array();

			if($detail['shop_in_tmall']){
				$tmp = explode("\n", $detail['shop_in_tmall']);
				$detail['shop_in_tmall'] = array();
				foreach($tmp as $line){
					list($name, $url) = explode('|', $line);
					$detail['shop_in_tmall'][] = array('name'=>$name, 'url'=>$url);
				}
			}else{
				$detail['shop_in_tmall'] = array();
			}

			D('cache')->set($key, $detail, MINUTE*10, true);
		}

		if($field)
			return $detail[$field];
		else
			return $detail;
	}

	//获取品牌名称
	function getName($brand_id, $full=true){

		$brand = $this->detail($brand_id);
		if(!$brand)return '';
		if(!$full){
			return $brand['name']?$brand['name']:$brand['name_en'];
		}else{
			$tmp = array($brand['name'], $brand['name_en']);
			return join('/', arrayClean($tmp));
		}
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

			if($brand['name'] && preg_match("/{$brand['name']}/i", $detail['name']) && (!$brand['ex_rule'] || !preg_match("/(".$brand['ex_rule'].")/i", $detail['name']))){
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
			//加入待审核列表
			$this->db('promotion.review_brand')->add($sp, $goods_id);

			//标识品牌在商城有卖
			$brand = $this->detail($brand_hit['id']);
			if(!in_array($sp, $brand['shop_in_b2c']) && $sp <> 'taobao' && $sp <> 'tmall'){
				$brand['shop_in_b2c'][] = $sp;
				$this->db('promotion.brand')->save(array('id'=>$brand_hit['id'], 'shop_in_b2c'=>join(',', $brand['shop_in_b2c'])));
				$key = 'brand:detail:'.$brand_hit['id'];
				D('cache')->clean($key);
			}

			return $brand_hit;
		}else{

			$this->db('promotion.goods')->update($sp, $goods_id, array('brand_id'=>0));
			$this->db('promotion.queue_promo2cat')->update($sp, $goods_id, array('brand_id'=>0));
		}
	}

	//获取一周发生过促销的品牌数
	function getStat(){

		$num_week = $this->db('promotion.queue_promo2cat')->query("SELECT count(DISTINCT brand_id) nu FROM queue_promo2cat WHERE createtime > '".date('Y-m-d', time()-WEEK)."'");
		$num_week = @$num_week[0][0]['nu'];
		return array('num_week'=>intval($num_week));
	}

	/**
	 * 环状获取指定品牌的相关品牌
	 * @param  int     $brand_id 品牌ID
	 * @param  boolean $in_cat   是否在品牌内
	 * @return [type]            [description]
	 */
	function relativeBrands($brand_id, $in_cat = '', $limit = 20){

		if(!$brand_id)return;
		$half = intval($limit/2);

		if($in_cat){
			$cond_search = array('id'=>'< ' . $brand_id, 'cat'=>"REGEXP {$in_cat}");
			$cond_fix = array('cat'=>"REGEXP {$in_cat}");
		}else{
			$cond_search = array('id'=>'< ' . $brand_id);
			$cond_fix = array();
		}

		$brands_prev_1 = $this->db('promotion.brand')->findAll($cond_search, 'id,name,name_en,weight', 'id DESC', $half);
		$brands_prev_2 = array();
		if(!$brands_prev_1 || count($brands_prev_1) < $half){
			$brands_prev_2 = $this->db('promotion.brand')->findAll($cond_fix, 'id,name,name_en,weight', 'id DESC', $half - count($brands_prev_1));
		}
		$brands_prev = array_merge((array)clearTableName($brands_prev_1), (array)clearTableName($brands_prev_2));

		if($in_cat){
			$cond_search = array('id'=>'> ' . $brand_id, 'cat'=>"REGEXP {$in_cat}");
			$cond_fix = array('cat'=>"REGEXP {$in_cat}");
		}else{
			$cond_search = array('id'=>'> ' . $brand_id);
			$cond_fix = array();
		}
		$brands_next_1 = $this->db('promotion.brand')->findAll($cond_search, 'id,name,name_en,weight', 'id ASC', $half);
		$brands_next_2 = array();
		if(!$brands_next_1 || count($brands_next_1) < $half){
			$brands_next_2 = $this->db('promotion.brand')->findAll($cond_fix, 'id,name,name_en,weight', 'id ASC', $half - count($brands_next_1));
		}
		$brands_next = array_merge((array)clearTableName($brands_next_1), (array)clearTableName($brands_next_2));

		return array_merge($brands_prev, $brands_next);
	}
}
?>