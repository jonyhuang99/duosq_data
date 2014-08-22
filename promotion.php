<?php
//DAL:特卖管理模块
namespace DAL;

class Promotion extends _Dal {

	//判断是否支持该商城商品的特卖信息抓取
	//url:frist 返回匹配商城第一个链接模板(用于返利网)，否则进行精确匹配
	function support($sp, $url='first', &$url_id=''){

		$rules = C('rule_goods_url');
		foreach($rules as $tpl_no => $rule){

			if($url == 'first'){
				if($rule['sp'] == $sp){
					return $tpl_no;
				}
			}else{
				$url = urldecode(urldecode($url));
				if($rule['sp'] == $sp){
					foreach($rule['m'] as $match_rule){
						if(preg_match("/{$match_rule}/i", $url, $m)){
							$url_id = $m[1];
							return $tpl_no;
						}
					}
				}
			}
		}
	}

	/**
	 * 将原链接生成规范的内部商品链接
	 * @param  [type] $url [description]
	 * @return [type]      array('url'=>)
	 */
	function formatUrl($url){

		$rule = '';
	}

	//封装商品url
	//type支持pc & mobi
	function buildUrl($url_tpl, $url_id, $type='pc'){

		if(!$url_tpl || !$url_id)return;
		$rule = C('rule_goods_url', $url_tpl);
		$url = preg_replace('/{.+?}/', $url_id, $rule['tpl']);

		if($type == 'pc'){
			return array_shift(explode('|', $url));
		}else{
			return array_pop(explode('|', $url));
		}
	}

	//从商品url获取商品信息
	function goodsDetailByUrl($url){

		if(!$url)return;
		return $this->api('fanli')->goodsDetail($url);
	}

	//从商品url获取商品价格信息
	function goodsPriceByUrl($url){

		if(!$url)return;
		$ret = $this->api('fanli')->goodsPrice($url);
		if($ret && !preg_match('/^[,]+$/', $ret['priceList'])){
			$tmp_price = explode(',', $ret['priceList']);
			$tmp_date = explode(',', $ret['priceDate']);

			$price_trend = array();
			$i = 0;
			foreach($tmp_date as $sd){

				$d = time()-DAY*(count($tmp_date)-$i-1);
				if(date('m-d', $d) == $sd){
					$date = date('Y-m-d', $d);
					$price_trend[$date] = $tmp_price[$i];
				}
				$i++;
			}
			$ret['priceList'] = $price_trend;
			return $ret;
		}
	}

	//获取待指派分类的商品列表
	function getWaitAssignCatGoods(){

		$goods = $this->db('promotion.queue_promo')->findAll(array('cat_assign'=>0), '', '', 100);
		return clearTableName($goods);
	}

	//解析分类
	function parseCat($sp, $goods){

		$detail = $this->db('promotion.goods')->detail($sp, $goods);
	}

	//读取商品分类配置
	function getCatConfig($all = false){

		if($all)
			$all=1;
		else
			$all=0;

		//判断mcache防止文件发生改动
		$cache = $this->mcache()->get('cat_config_'.$all);
		if($cache)return $cache;

		$file = file(MYCONFIGS . 'goods_cat');
		if(!$file)return false;

		$ret = array();
		foreach($file as $line){

			$count = substr_count($line, "\t");
			$line = trim($line);
			if($count == 0){
				$ret[$line] = '';
				$last_cat = $line;
			}

			if($all){
				if($count == 1){
					$ret[$last_cat][$line] = '';
					$last_mid_cat = $line;
				}
				if($count == 2){
					$ret[$last_cat][$last_mid_cat][] = trim(preg_replace('/\(.+?\)/i', '', $line));
				}

			}else{
				if($count == 2){
					$ret[$last_cat][] = trim(preg_replace('/\(.+?\)/i', '', $line));
				}
			}
		}

		//缓存1分钟
		$this->mcache()->set('cat_config_'.$all, $ret, MINUTE);
		return $ret;
	}

	//从商品的子分类找出父分类
	function subcat2cat($subcat){

		if(!$subcat)return;
		static $subcat2cat = array();
		if(isset($subcat2cat[$subcat]))return $subcat2cat[$subcat];

		$cat_config = $this->getCatConfig();
		foreach($cat_config as $c_cat => $c_subcats){
			foreach($c_subcats as $c_subcat){
				$subcat2cat[$c_subcat] = $c_cat;
				if($c_subcat == $subcat){
					return $c_cat;
				}
			}
		}
		return '';
	}

	//从商品的子分类找出中分类
	function subcat2midcat($subcat){

		if(!$subcat)return;
		static $subcat2midcat = array();
		if(isset($subcat2midcat[$subcat]))return $subcat2midcat[$subcat];

		$cat_config = $this->getCatConfig(true);
		foreach($cat_config as $c_cat => $values){
			foreach ($values as $midcat => $c_subcats) {
				foreach($c_subcats as $c_subcat){
					$subcat2midcat[$c_subcat] = $midcat;
					if($c_subcat == $subcat){
						return $midcat;
					}
				}
			}
		}
		return '';
	}

	//从商品的中分类找出分类
	function midcat2cat($midcat){

		if(!$midcat)return;
		static $midcat2cat = array();
		if(isset($midcat2cat[$midcat]))return $midcat2cat[$midcat];

		$cat_config = $this->getCatConfig(true);
		foreach($cat_config as $c_cat => $c_subcats){
			foreach($c_subcats as $c_midcat => $c_subcat){
				$midcat2cat[$c_midcat] = $c_cat;
				if($c_midcat == $midcat){
					return $c_cat;
				}
			}
		}
		return '';
	}

	//从商品的中分类找出子分类
	function midcat2subcat($midcat){

		if(!$midcat)return;
		static $midcat2subcat = array();
		if(isset($midcat2subcat[$midcat]))return $midcat2subcat[$midcat];

		$cat_config = $this->getCatConfig(true);
		foreach($cat_config as $c_cat => $c_subcats){
			foreach($c_subcats as $c_midcat => $c_subcat){
				$midcat2subcat[$c_midcat] = $c_subcat;
				if($c_midcat == $midcat){
					return $c_subcat;
				}
			}
		}
		return '';
	}

	//获取商品分类匹配规则(全填读取指定子分类规则，任一不填返回所有分类匹配规则)
	function getCatRules($cat='', $subcat=''){

		return $this->redis('keys')->goodsCatRules($cat, $subcat);
	}

	//设置商品分类匹配规则
	function setCatRules($cat, $subcat, $rules){

		if(!$cat || !$subcat || !$rules)return;
		$this->redis('keys')->goodsCatRules($cat, $subcat, $rules);
		return true;
	}

	//获取商品中分类排除规则
	function getMidcatExRule($cat, $midcat){

		if(!$cat || !$midcat)return;
		$ret =  $this->redis('keys')->goodsMidcatExRule($cat, $midcat);
		return $ret;
	}

	//保存商品中分类排除规则
	function setMidcatExRule($cat, $midcat, $rule){

		if(!$cat || !$midcat)return;
		$this->redis('keys')->goodsMidcatExRule($cat, $midcat, $rule);
		return true;
	}

	//获取商品详情
	function goodsDetail($sp, $goods_id){

		//static $d = array();
		if(!$sp || !$goods_id)return;
		//if(isset($d[$sp][$goods_id]))return $d[$sp][$goods_id];

		$key = 'goods:detail:sp:'.$sp.':goods_id:'.$goods_id;
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		$detail = $this->db('promotion.goods')->detail($sp, $goods_id);
		if($detail){
			$detail['url'] = $this->buildUrl($detail['url_tpl'], $detail['url_id']);
			if($detail['cat'])$detail['cat'] = explode('|', $detail['cat']);
			if($detail['subcat'])$detail['subcat'] = explode('|', $detail['subcat']);
		}else{
			return false;
		}

		if($fix_now_price = $this->getFixNowPrice($sp, $goods_id)){
			$detail['price_now'] = $fix_now_price;
		}

		$invalid = false;

		if($detail['status'] == \DB\Goods::STATUS_SELL_OUT){
			$invalid = 'sell_out';
		}

		if($detail['status'] == \DB\Goods::STATUS_INVALID || $detail['status'] == \DB\Goods::STATUS_INVALID_FORCE){
			$invalid = 'invalid';
		}

		$detail['invalid'] = $invalid;

		D('cache')->set($key, $detail, MINUTE*10, true);
		//$d[$sp][$goods_id] = $detail;
		return $detail;
	}

	//匹配并更新商品分类信息
	function matchGoodsCat($sp, $goods_id){

		if(!$sp || !$goods_id)return;

		$detail = $this->goodsDetail($sp, $goods_id);
		//人工审核分类后不再自动匹配
		if(!$detail || $detail['cat_review'])return;

		$name = $detail['name'];
		$all_rules = $this->getCatRules();
		$match_subcat = array();

		foreach($all_rules as $cat => $val){
			foreach($val as $subcat => $rules){

				$midcat = $this->subcat2midcat($subcat);

				$midcat_ex_rule = $this->getMidcatExRule($cat, $midcat);
				$midcat_ex_rule = r('/', '\/', $midcat_ex_rule);
				if($midcat_ex_rule && preg_match("/{$midcat_ex_rule}/i", $name)){
					continue;
				}

				//用价格做特殊修正
				if($midcat == '品牌手机' && $detail['price_now'] < 200)continue;
				if($midcat == '电脑整机' && $detail['price_now'] < 200)continue;

				foreach($rules as $rule){
					if(is_array($rule)){
						$match = true;
						foreach ($rule as $m) {
							if(stripos($name, $m)===false){
								$match = false;
							}
						}
						if($match){
							$match_subcat[$subcat] = 1;
						}
					}else{
						$rule = trim($rule);
						if($rule && preg_match("/{$rule}/i", $name)){
							$match_subcat[$subcat] = 1;
						}
					}
				}
			}
		}

		//清除旧分类，如果手动进行过设置，后续应想办法进行锁定或者区分
		if($match_subcat){
			/*
			$old_subcat = $detail['subcat'];
			if($old_subcat){
				foreach($old_subcat as $subcat){
					$match_subcat[$subcat] = 1;
				}
			}
			*/

			$match_subcat = array_keys($match_subcat);
			$this->updateGoodsCat($sp, $goods_id, $match_subcat);
			return $match_subcat;
		}else{
			$this->clearGoodsCat($sp, $goods_id);
		}
	}

	//更新商品分类
	function updateGoodsCat($sp, $goods_id, $subcats){

		if(!$sp || !$goods_id || !$subcats)return;

		$match_cats = array();
		foreach($subcats as $subcat){
			if($cat = $this->subcat2cat($subcat)){
				$match_cats[$cat] = 1;
			}
		}

		$cat = array();
		if($match_cats){
			$cats = array_slice(array_keys($match_cats), 0, 5);
			$cat_str = join('|', $cats);
		}
		$subcat_str = join('|', array_slice($subcats, 0, 10));

		$ret = $this->updateGoods($sp, $goods_id, array('cat'=>$cat_str, 'subcat'=>$subcat_str));
		if(!$ret)return false;

		//同步promo分类
		$promo = $this->promoDetail($sp, $goods_id);

		if($promo){
			$promo2cat = $this->db('promotion.queue_promo2cat')->findAll(array('sp'=>$sp, 'goods_id'=>$goods_id));
			$promo2cat = clearTableName($promo2cat);
			if($promo2cat && count($promo2cat) == count($subcats)){
				$promo2cat_subcat = array();
				foreach($promo2cat as $p){
					$promo2cat_subcat[$p['subcat']] = 1;
				}
				foreach($subcats as $subcat){
					unset($promo2cat_subcat[$subcat]);
				}

				if(!count($promo2cat_subcat)){
					$this->markPromoHasCat($sp, $goods_id);
					return $ret;
				}
			}

			$this->db('promotion.queue_promo2cat')->query("DELETE FROM queue_promo2cat WHERE sp = '{$sp}' AND goods_id = '{$goods_id}'");

			foreach($subcats as $subcat){
				$this->db('promotion.queue_promo2cat')->add(array('sp'=>$sp,'goods_id'=>$goods_id,'cat'=>$this->subcat2cat($subcat),'subcat'=>$subcat,'type'=>$promo['type'],'createtime'=>$promo['createtime']));
			}

			$this->markPromoHasCat($sp, $goods_id);
		}

		return $ret;
	}

	//清空商品分类
	function clearGoodsCat($sp, $goods_id){

		$ret = $this->db('promotion.goods')->update($sp, $goods_id, array('cat'=>'', 'subcat'=>''));
		$this->db('promotion.queue_promo2cat')->delete($sp, $goods_id);
		$this->db('promotion.queue_promo')->update($sp, $goods_id, array('cat_assign'=>0));
	}

	/**
	 * 新增商品
	 * @return bool         是否新增成功
	 */
	function importGoods($sp, $url_tpl, $url_id, $data_from, $data_category=''){

		if(!$sp || !$url_tpl || !$url_id || !$data_from)return false;

		$data = array();
		$data['sp'] = $sp;
		$data['url_tpl'] = $url_tpl;
		$data['url_id'] = $url_id;
		$data['data_from'] = $data_from;
		$data['data_category'] = $data_category;

		//判断商品库是否已存在
		$hit = $this->db('promotion.goods')->search($sp, array('url_tpl'=>$url_tpl, 'url_id'=>$url_id), 1);
		if($hit){
			$goods_id = $hit['id'];
		}else{
			$url = D('promotion')->buildUrl($url_tpl, $url_id);
			$goods_detail = D('promotion')->goodsDetailByUrl($url);

			if(!$goods_detail)return false;
			$data['name'] = $goods_detail['name'];
			$data['price_now'] = $goods_detail['price_now'];
			$data['pic_url'] = $goods_detail['pic_url'];

			$goods_id = $this->db('promotion.goods')->add($data['sp'], arrayClean($data));
			if($goods_id){
				$this->redis('promotion')->goodsCounter($data['sp']);
			}
		}

		//标识访问过该商品，等待价格抓取
		if($goods_id){
			$this->db('promotion.queue_visit')->visited($data['sp'], $goods_id);
			//标识商品被导入次数，用来发现新热点
			$this->redis('promotion')->saleCounter($sp, $goods_id);
			//更新了销量，触发重新计算促销商品权重
			D('weight')->update($sp, $goods_id);

			//更新商品周销量
			if($hit){
				$saled = $this->redis('promotion')->getSaleCount($sp, $goods_id);
				$saled = $saled + C('comm', 'promo_import_goods_sales_min');
				if(iSp($sp)){
					$saled = $saled * 10 + $goods_id%50;
				}else{
					$saled = $saled * 25 + $goods_id%50;
				}
				if($hit['saled'] < $saled){
					$this->db('promotion.goods')->update($sp, $goods_id, array('saled'=>$saled));
				}
			}
		}

		return $goods_id;
	}

	//获取最近被访问过的商品
	function getLastVisitGoods(){

		return $this->db('promotion.queue_visit')->getLastVisit(60);
	}

	//更新商品信息
	function updateGoods($sp, $goods_id, $new_data){

		if(!$sp || !$goods_id || !$new_data)return;
		$ret = $this->db('promotion.goods')->update($sp, $goods_id, $new_data);
		if($ret){
			$key = 'goods:detail:sp:'.$sp.':goods_id:'.$goods_id;
			D('cache')->clean($key);
		}
		return $ret;
	}

	//更新商品价格走势
	function updateGoodsPrice($sp, $goods_id, $price_trend, $price_min, $price_max, $price_now){

		if(!$sp || !$goods_id)return;
		$ret = $this->updateGoods($sp, $goods_id, array('price_trend'=>$price_trend, 'price_min'=>$price_min, 'price_max'=>$price_max, 'price_now'=>$price_now, 'price_update'=>date('Y-m-d')));

		if($ret){
			$this->db('promotion.queue_visit')->detected($sp, $goods_id);
			return $ret;
		}
	}

	//更新商品信息
	function updateGoodsStatus($sp, $goods_id, $status){

		if(!$sp || !$goods_id)return;
		return $this->updateGoods($sp, $goods_id, array('status'=>$status));
	}

	//标记该商品是降价商品(type 1:特卖 2:热卖)
	function markPromoDiscount($sp, $goods_id, $price_avg, $price_now){

		if(!$sp || !$goods_id || !$price_avg || !$price_now)return;
		$promo = $this->promoDetail($sp, $goods_id);
		if(!$promo){


			$config = C('comm', 'promo_auto_pass');
			//是否自动发布
			if($config['discount']){
				$status = \DB\QueuePromo::STATUS_NORMAL;
			}else{
				$status = \DB\QueuePromo::STATUS_WAIT_REVIEW;
			}

			$detail = $this->goodsDetail($sp, $goods_id);
			$ret = $this->db('promotion.queue_promo')->add(array('status'=>$status, 'sp'=>$sp, 'goods_id'=>$goods_id, 'cat'=>$detail['cat'], 'subcat'=>$detail['subcat'], 'price_avg'=>$price_avg, 'price_now'=>$price_now, 'type'=>\DB\QueuePromo::TYPE_DISCOUNT));
			//自动匹配分类，快速覆盖新增特卖，re_match脚本做全量更新同步分类规则变化
			$this->matchGoodsCat($sp, $goods_id);
			D('brand')->matchAndUpdateBrand($sp, $goods_id);
			//加入待审核列表
			$this->db('promotion.review')->add(\DB\Review::TYPE_PROMO, array('sp'=>$sp, 'goods_id'=>$goods_id));
			//加入搜索索引，快速覆盖新增特卖，rebuild_index脚本做全量更新，防止有商品上下线
			D('search')->buildIndex($sp, $goods_id); //-有错误
			if($ret)$this->redis('promotion')->promoCounter($sp, $goods_id); //--有错误
			return true;
		}else{
			//如果价格更低，允许更新
			if($price_now < $promo['price_now']){
				$this->db('promotion.queue_promo')->update($sp, $goods_id, array('price_now'=>$price_now));
			}
		}
	}

	//标记该商品是活动商品(新增返回true)
	function markPromoHuodong($sp, $goods_id, $price_avg, $price_now, $hd_content, $hd_begin='', $hd_expire=''){

		if(!$sp || !$goods_id || !$price_avg || !$price_now)return;
		if(!$this->promoDetail($sp, $goods_id)){

			$config = C('comm', 'promo_auto_pass');
			//是否自动发布
			if($config['discount']){
				$status = \DB\QueuePromo::STATUS_NORMAL;
			}else{
				$status = \DB\QueuePromo::STATUS_WAIT_REVIEW;
			}

			$detail = $this->goodsDetail($sp, $goods_id);
			$ret = $this->db('promotion.queue_promo')->add(array('status'=>$status, 'sp'=>$sp, 'goods_id'=>$goods_id, 'cat'=>$detail['cat'], 'subcat'=>$detail['subcat'], 'price_avg'=>$price_avg, 'price_now'=>$price_now, 'hd_content'=>$hd_content, 'hd_begin'=>$hd_begin, 'hd_expire'=>$hd_begin, 'type'=>\DB\QueuePromo::TYPE_HUODONG));
			//自动匹配分类，快速覆盖新增特卖，re_match脚本做全量更新同步分类规则变化
			$this->matchGoodsCat($sp, $goods_id);
			D('brand')->matchAndUpdateBrand($sp, $goods_id);
			//加入待审核列表
			$this->db('promotion.review')->add(\DB\Review::TYPE_PROMO, array('sp'=>$sp, 'goods_id'=>$goods_id));
			//加入搜索索引，快速覆盖新增特卖，rebuild_index脚本做全量更新，防止有商品上下线
			D('search')->buildIndex($sp, $goods_id);
			if($ret)$this->redis('promotion')->promoCounter($sp, $goods_id);
			return true;
		}
	}

	//标识特卖商品已有分类
	function markPromoHasCat($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		return $this->db('promotion.queue_promo')->update($sp, $goods_id, array('cat_assign'=>1));
	}

	//标识推荐到APP
	function markRecommendApp($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		return $this->db('promotion.queue_promo2cat')->update($sp, $goods_id, array('app'=>1));
	}

	//获取特卖详情
	function promoDetail($sp, $goods_id){

		//static $d = array();
		if(!$sp || !$goods_id)return;
		//if(isset($d[$sp][$goods_id]))return $d[$sp][$goods_id];

		$key = 'promo:detail:sp:'.$sp.':goods_id:'.$goods_id;
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		$promo = $this->db('promotion.queue_promo')->find(array('sp'=>$sp, 'goods_id'=>$goods_id));
		if(!$promo)return;
		$promo = clearTableName($promo);
		//计算无效状态
		$invalid = false;
		$goods_detail = $this->goodsDetail($sp, $goods_id);

		//此处活动报价极有可能与探测价格不符，因此仅判断降价类型的特卖
		if($promo['type'] == \DB\QueuePromo::TYPE_DISCOUNT){
			if($goods_detail['price_now'] > $promo['price_now']){
				$invalid = 'price_up';
			}
		}

		//如果进行了强制修正，说明即使是活动，也有可能不准
		if($fix_now_price = $this->getFixNowPrice($sp, $goods_id)){
			if($fix_now_price > $promo['price_now']){
				$invalid = 'price_up';
			}
		}

		//修正当前价格比特卖价格更低，用户就觉得不准
		if($promo['price_now'] > $goods_detail['price_now']){
			$promo['price_now'] = $goods_detail['price_now'];
		}

		if($promo['type'] == \DB\QueuePromo::TYPE_HUODONG){
			if(strtotime($promo['hd_expire']) > 0 && strtotime($promo['hd_expire']) < strtotime(date('Y-m-d'))){
				$invalid = 'hd_expired';
			}
		}

		if($goods_detail['status'] == \DB\Goods::STATUS_SELL_OUT){
			$invalid = 'sell_out';
		}

		if($goods_detail['status'] == \DB\Goods::STATUS_INVALID || $goods_detail['status'] == \DB\Goods::STATUS_INVALID_FORCE){
			$invalid = 'invalid';
		}

		$promo['invalid'] = $invalid;
		D('cache')->set($key, $promo, MINUTE*10, true);
		//$d[$sp][$goods_id] = $promo;
		return $promo;
	}

	//更新特卖信息
	function updatePromo($sp, $goods_id, $new_data){

		if(!$sp || !$goods_id || !$new_data)return;
		$ret = $this->db('promotion.queue_promo')->update($sp, $goods_id, $new_data);
		if($ret){
			$key = 'promo:detail:sp:'.$sp.':goods_id:'.$goods_id;
			D('cache')->clean($key);
		}
		return $ret;
	}

	/**
	 * 获取特卖商品列表
	 * @param  [type]  $pn            [description]
	 * @param  array   $cat_condition 分类搜索条件(cat/subcat)
	 * @param  integer $show          [description]
	 * @param  integer $maxPages      [description]
	 * @return [type]                 [description]
	 */
	function getList($pn, $cat_condition=array(), $show = 3, $need_huodong=true) {

		$key = 'promo:get_list:cond:'.md5(serialize($cat_condition)).':show:'.$show.':page:'.intval(@$_GET['page']);
		$cache = D('cache')->get($key);
		if($cache && $use_cache){
			$pn->controller->set('page_count', D('cache')->ret(D('cache')->get($key.':page_count')));
			return D('cache')->ret($cache);
		}

		$condition = arrayClean($cat_condition);
		static $huodong_repeat=array();

		//page = 0 返回总页数
		$pn->show = $show;
		$pn->sortBy = 'weight';
		$pn->direction = 'desc';

		//最多加载20页
		if(isset($_GET['page']) && $_GET['page'] > C('comm', 'promo_cat_max_page'))
			return false;

		$condition_str = array();
		foreach($condition as $field => $c){

			if(is_array($c)){
				$condition_str[] = "{$field} in ('" . join("','", $c) . "')";
			}elseif(preg_match('/^(?:in|not in)/i', $c)){
				$condition_str[] = $field . ' ' . $c;
			}elseif(preg_match('/^(<|>) (.+$)/i', $c, $m)){

				$condition_str[] = "{$field} {$m[1]} '{$m[2]} '";
			}else {
				$condition_str[] = "{$field} = '{$c}'";
			}
		}

		$condition_str[] = "sp <> 'taobao'";
		$condition_str = join(' AND ', $condition_str);
		list($order, $limit, $page) = $pn->init($condition_str, array('modelClass' => $this->db('promotion.queue_promo2cat'), 'fields'=>'DISTINCT sp, goods_id, type'));

		$result = $this->db('promotion.queue_promo2cat')->findAll($condition_str, 'DISTINCT sp, goods_id, type', 'ORDER BY weight DESC, id DESC', $limit, $page);
		$result = clearTableName($result);
		if(!$result)$result = array();

		$this->db('promotion.queue_promo');

		if(!isset($_GET['page']) && $need_huodong){

			//带上3个活动数据
			$condition['type'] = \DB\QueuePromo::TYPE_HUODONG;
			foreach ($result as $promo) {
				if($promo['type'] == \DB\QueuePromo::TYPE_HUODONG){
					$huodong_repeat[$promo['goods_id']] = 1;
				}
			}

			if($huodong_repeat){
				$jump_goods_ids = join(',', array_keys($huodong_repeat));
				$condition['goods_id'] = "not in ({$jump_goods_ids})";
			}

			$result_hodong = $this->db('promotion.queue_promo2cat')->findAll($condition, 'DISTINCT sp, goods_id, type', 'ORDER BY weight DESC, ORDER BY id DESC', 3);
			$result_hodong = clearTableName($result_hodong);

			if($result_hodong){
				foreach($result_hodong as $huodong){
					$huodong_repeat[$huodong['goods_id']] = 1;
					array_unshift($result, $huodong);
				}
			}
		}

		$result = $this->renderPromoDetail($result);
		if($result)$result = array_slice($result, 0, $show+3);
		D('cache')->set($key, $result, MINUTE*2, true);
		//修正由于缓存，无法set $paging变量，导致调用分页白屏错误
		D('cache')->set($key.':page_count', $pn->paging['pageCount'], MINUTE*2+1, true);
		$pn->controller->set('page_count', $pn->paging['pageCount']);

		return $result;
	}

	//渲染特卖信息
	function renderPromoDetail($result){

		if(!$result)return;
		$new_ret = array();
		$this->db('promotion.goods');
		$this->db('promotion.queue_promo');
		foreach ($result as $ret) {

			$promo_detail = $this->promoDetail($ret['sp'], $ret['goods_id']);
			if(!$promo_detail || $promo_detail['status'] != \DB\QueuePromo::STATUS_NORMAL)continue;

			$goods_detail = $this->goodsDetail($ret['sp'], $ret['goods_id']);
			if(!$goods_detail)continue;

			$tmp = $promo_detail;

			if($tmp['hd_content']){
				$tmp['hd_content'] = strip_tags($tmp['hd_content']);
			}else{
				$saled_str = '';
				$saled = $this->redis('promotion')->getSaleCount($ret['sp'], $ret['goods_id']);
				$saled = $saled + C('comm', 'promo_import_goods_sales_min');
				if(iSp($ret['sp'])){
					$saled = $saled * 10 + $ret['goods_id']%50;
				}else{
					$saled = $saled * 25 + $ret['goods_id']%50;
				}

				if($saled > 300){//周销量超过300为热销

					$saled_str = "上周原价热销：<font class=blue>{$saled}</font>件<br />";
					$tmp['week_sales'] = $saled;
				}
				$tmp['dis_content'] = '90天实售：<font class="blue">¥'.price_yuan($tmp['price_avg']).'</font><br />'.$saled_str.'刚刚降至：<font class=orange>¥'.price_yuan($tmp['price_now']).'</font>，现在出手直接省掉了<font class=green>'.rate_diff($tmp['price_now'], $tmp['price_avg']).'%</font>哟~';
			}

			if(time() - strtotime($promo_detail['createdate']) < DAY){
				$tmp['is_new'] = true;
			}
			$tmp['name'] = $goods_detail['name'];
			$tmp['pic_url'] = $goods_detail['pic_url'];
			$tmp['cat'] = $goods_detail['cat'];
			$tmp['url_tpl'] = $goods_detail['url_tpl'];
			$tmp['url_id'] = $goods_detail['url_id'];
			$new_ret[] = $tmp;
		}

		return $new_ret;
	}

	//获取经过人工修正后的当前价格
	function getFixNowPrice($sp, $goods_id){
		//纠正当前价格
		if($price_now_fixed = $this->db('promotion.price_fix')->field('price_now', array('sp'=>$sp, 'goods_id'=>$goods_id, 'expire'=>'>= '.date('Y-m-d')))){
			return $price_now_fixed;
		}
	}

	//获取入库商品数，特卖数
	function getStat(){

		$num_goods = $this->redis('promotion')->getGoodsCount();
		$num_promo_sp = $this->redis('promotion')->getPromoSpCount();
		$num_promo_sp_today = $this->redis('promotion')->getPromoSpCountDate();
		$num_promo_cat_today = $this->redis('promotion')->getPromoCatCountDate();
		return array('num_goods'=>$num_goods, 'num_promo_sp'=>$num_promo_sp, 'num_promo_sp_today'=>$num_promo_sp_today, 'num_promo_cat_today'=>$num_promo_cat_today);
	}

	//获取商品搜索提示
	function getSuggest($keyword, $limit = 10, $search_goods=true){

		if(!$keyword)return;
		$key = 'promotion:suggest:keyword:'.md5($keyword);
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		$suggest = $this->api('taobao')->getSuggest($keyword, 15);

		$new_suggest = array();
		if($suggest){
			foreach($suggest as $s_k){
				$hit = D('search')->promo($s_k);
				if($hit){
					$new_suggest[] = $s_k;
				}
				if(count($new_suggest) >= $limit)break;
			}

			if(!$new_suggest && $search_goods){
				foreach($suggest as $s_k){
					if(count($new_suggest) >= $limit)break;
					$hit = D('search')->goods($s_k);
					if($hit){
						$new_suggest[] = $s_k;
					}
				}
			}
		}

		D('cache')->set($key, $new_suggest, DAY*10, true);
		return $new_suggest;
	}

	//获取一个随机促销
	function getRandPromo(){

		return $this->db('promotion.queue_promo2cat')->find("subcat<>'' AND brand_id<>'' AND createtime>'".date('Y-m-d', time()-DAY*300)."'", '', 'rand()');
	}
}
?>