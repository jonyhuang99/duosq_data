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
		$brands = $this->db('promotion.brand')->findAll($condition, 'id,name,name_en,intro,weight', 'weight DESC', $limit);
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
			if(!$detail)return;
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

			if($detail['cat'])
				$detail['cat'] = explode(',', $detail['cat']);
			else
				$detail['cat'] = array();

			D('cache')->set($key, $detail, MINUTE*10, true);
		}

		if($field)
			return $detail[$field];
		else
			return $detail;
	}

	//更新品牌搜索索引
	function updateSearchIndex($id){

		if(!$id)return;
		$brand = $this->detail($id);
		$name_search = preg_replace('/[^\\x7f-\\xff0-9a-z]/i', '', $brand['name'].$brand['name_en']);
		$this->db('promotion.brand')->update($id, array('name_search'=>$name_search));
		return $name_search;
	}

	//更新品牌信息
	function update($id, $data){

		if(!$id || !$data)return;
		$ret = $this->db('promotion.brand')->update($id, $data);
		if($ret){
			$key = 'brand:detail:'.$id;
			D('cache')->clean($key);
			if($ret)$this->updateSearchIndex($id);
		}
		return $ret;
	}

	//更新品牌信息
	function updateWork($brand_id, $data){

		if(!$brand_id || !$data)return;
		$work_id  = $this->db('promotion.brand_work')->field('id', array('brand_id'=>$brand_id));
		$ret = $this->db('promotion.brand_work')->update($work_id, $data);
		return $ret;
	}

	//更新品牌信息
	function add($data){

		if(!$data)return;
		$ret = $this->db('promotion.brand')->add($data);
		if($ret)$this->updateSearchIndex($ret);
		return $ret;
	}

	//随机获取品牌
	function getRand(){
		$brand = $this->db('promotion.brand')->find("intro<>''", '', 'rand()');
		$brand = clearTableName($brand);
		if(!$brand)return;
		return $this->detail($brand['id']);
	}

	//随机获取品牌资讯
	function getRandNews(){
		$news = $this->db('promotion.brand_news')->find('', '', 'rand()');
		$news = clearTableName($news);
		return $news;
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
		//人工审核品牌后不再自动匹配
		if(!$detail || !$detail['cat'] || $detail['brand_review'])return;

		$key = 'brand:details:cat:'.md5(serialize($detail['cat']));

		$cache = D('cache')->get($key);
		if($cache){
			$brands = D('cache')->ret($cache);
		}else{
			$brands = $this->db('promotion.brand')->findAll(array('cat'=>"regexp (".join('|',$detail['cat']).')'), 'id,name,name_en,sp_rule,ex_rule,left_match');

			$brands = clearTableName($brands);
			D('cache')->set($key, $brands, HOUR);
		}

		$brand_hit = false;
		foreach($brands as $brand){

			if($brand['left_match']){
				$is_left = '^';
			}else{
				$is_left = '';
			}

			$brand['name'] = r('/', '\/', $brand['name']);
			$brand['name_en'] = r('/', '\/', $brand['name_en']);

			if($brand['name'] && mb_strlen($brand['name'], 'UTF-8')>1 && preg_match("/{$is_left}{$brand['name']}/i", $detail['name']) && (!$brand['ex_rule'] || !preg_match("/(".$brand['ex_rule'].")/i", $detail['name']))){
				$brand_hit = $brand;
				break;
			}else if($brand['name_en'] && mb_strlen($brand['name_en'], 'UTF-8')>1 && preg_match("/{$is_left}{$brand['name_en']}[^a-z0-9\+\·\'\’\:\-\&]/i", $detail['name']) && (!$brand['ex_rule'] || !preg_match("/({$brand['ex_rule']})/i", $detail['name']))){
				$brand_hit = $brand;
				break;
			}else if($brand['sp_rule'] && preg_match("/({$brand['sp_rule']})[^a-z0-9\+\·\'\’\:\-\&]/i", $detail['name']) && (!$brand['ex_rule'] || !preg_match("/({$brand['ex_rule']})/i", $detail['name']))){
				$brand_hit = $brand;
				break;
			}
		}

		if($brand_hit){
			$ret = $this->updateGoodsBrand($sp, $goods_id, $brand_hit['id']);
			if($ret)return $brand_hit['id'];
		}else{
			$this->db('promotion.goods')->update($sp, $goods_id, array('brand_id'=>0));
			$this->db('promotion.queue_promo2cat')->update($sp, $goods_id, array('brand_id'=>0));
		}
	}

	//更新商品品牌
	function updateGoodsBrand($sp, $goods_id, $brand_id=0){

		$this->db('promotion.goods')->update($sp, $goods_id, array('brand_id'=>$brand_id));
		$ret = $this->db('promotion.queue_promo2cat')->update($sp, $goods_id, array('brand_id'=>$brand_id));
		//清除品牌关联
		if(!$brand_id)return $ret;
		//标识品牌在商城有卖
		$brand = $this->detail($brand_id);
		if(!in_array($sp, $brand['shop_in_b2c']) && $sp <> 'taobao' && $sp <> 'tmall'){
			$brand['shop_in_b2c'][] = $sp;
			$this->update($brand_id, array('shop_in_b2c'=>join(',', $brand['shop_in_b2c'])));
		}

		D('promotion')->clearCache($sp, $goods_id);

		return $ret;
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