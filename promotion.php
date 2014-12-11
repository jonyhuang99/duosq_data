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

	//找出大分类下面的中分类
	function midcat($cat){

		if(!$cat)return;
		$cat_config = $this->getCatConfig(true);
		$midcats = array();
		foreach($cat_config as $c_cat => $values){
			if($c_cat != $cat)continue;
			foreach ($values as $midcat => $c_subcats) {
				$midcats[] = $midcat;
			}
		}
		return $midcats;
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

	//从子分类反查推荐的分类配置
	function nvRenSubcat2recommend($subcat){

		if(!$subcat)return;
		$conf = $this->nvRenSubcat2conf($subcat);
		if(!$conf)return;
		unset($conf['nv_category']);

		$recommend_conf = array();
		foreach($conf as $nv_cat => $c){
			if(isset($c['recommend'])){
				$recommend_conf[$nv_cat] = $c;
			}
		}
		return $recommend_conf;
	}

	//从子分类反查
	function nvRenSubcat2conf($subcat, $tag=''){

		if(!$subcat)return;
		$config = C('comm', 'category_nv_ren_jie');
		foreach($config as $nv_category => $conf){
			foreach($conf as $nv_cat => $c){

				if($c['subcat'] == $subcat){
					if($tag){
						if($c['tag'] == $tag){
							$c['nv_category'] = $nv_category;
							$c['nv_cat'] = $nv_cat;
							return $c;
						}
					}else{
						$conf['nv_category'] = $nv_category;
						return $conf;
					}
				}
			}
		}
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

	//获取商品子分类标签规则
	function getSubcatTags($subcat=''){

		static $config = array();
		if(!$subcat)return;
		if($config){
			if(isset($config[$subcat]))
				return $config[$subcat];
			return array();
		}
		$all_tags = C('goods_subcat_tag');
		$config = array();
		foreach ($all_tags as $subcat_str => $tags) {
			$subcats = explode('|', $subcat_str);
			foreach ($subcats as $i_subcat) {
				$config[$i_subcat] = $tags;
			}
		}

		if(isset($config[$subcat]))return $config[$subcat];
		return array();
	}

	//获取商品详情
	function goodsDetail($sp, $goods_id){

		//static $d = array();
		if(!$sp || !$goods_id)return;
		//if(isset($d[$sp][$goods_id]))return $d[$sp][$goods_id];

		$this->db('promotion.goods');
		$key = 'goods:detail:sp:'.$sp.':goods_id:'.$goods_id;
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		$detail = $this->db('promotion.goods')->detail($sp, $goods_id);

		if($detail){
			$detail['url'] = $this->buildUrl($detail['url_tpl'], $detail['url_id']);
			if($detail['cat'])
				$detail['cat'] = explode('|', $detail['cat']);
			else
				$detail['cat'] = array();

			if($detail['subcat'])
				$detail['subcat'] = explode('|', $detail['subcat']);
			else
				$detail['subcat'] = array();

			$detail['tags'] = $this->db('promotion.queue_promo2tag')->get($sp, $goods_id);
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
	function matchGoodsCat($sp, $goods_id, $fix_name='', &$reviewed=''){

		if(!$sp || !$goods_id)return;

		$detail = $this->goodsDetail($sp, $goods_id);
		//人工审核分类后不再自动匹配
		if($detail['cat_review'])$reviewed = true;
		if(!$detail || $detail['cat_review'])return;

		if(!$fix_name){
			$name = $detail['name'];
		}else{
			$name = $fix_name;
		}

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
							if(!$m)continue;//避免空规则
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

		//临时跟踪错误分类电烫斗
		if(stripos(join('', $match_subcat), '电烫斗')!==false){
			file_put_contents('/tmp/tmp_rule', "goods_id:{$goods_id}:".$all_rules."\n", 8);
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

			$this->deletePromo2cat($sp, $goods_id);

			foreach($subcats as $subcat){
				$this->db('promotion.queue_promo2cat')->add(array('sp'=>$sp,'goods_id'=>$goods_id,'cat'=>$this->subcat2cat($subcat),'subcat'=>$subcat,'type'=>$promo['type'],'createtime'=>$promo['createtime']));
			}

			$this->markPromoHasCat($sp, $goods_id);
			$this->clearCache($sp, $goods_id);
		}

		return $ret;
	}

	//增加商品子分类
	function addGoodsSubcat($sp, $goods_id, $subcat=''){

		if(!$sp || !$goods_id || !$subcat)return;
		$cat = $this->subcat2cat($subcat);
		if(!$cat)return;

		$promo = $this->promoDetail($sp, $goods_id);
		$goods = $this->goodsDetail($sp, $goods_id);
		array_unshift($goods['subcat'], $subcat);
		array_unshift($goods['cat'], $cat);

		$this->updateGoods($sp, $goods_id, array('cat'=>join('|', array_unique($goods['cat'])), 'subcat'=>join('|', array_unique($goods['subcat']))));

		return $this->db('promotion.queue_promo2cat')->add(array('sp'=>$sp,'goods_id'=>$goods_id,'cat'=>$cat,'subcat'=>$subcat,'type'=>$promo['type'],'createtime'=>$promo['createtime']));;
	}

	//匹配并更新商品标签信息
	function matchGoodsTag($sp, $goods_id, $fix_name=''){

		if(!$sp || !$goods_id)return;

		$detail = $this->goodsDetail($sp, $goods_id);

		if(!$fix_name){
			$name = $detail['name'];
		}else{
			$name = $fix_name;
		}

		if(!$detail['subcat'])return;

		$match_tags = array();
		foreach ($detail['subcat'] as $subcat) {
			$all_tags = $this->getSubcatTags($subcat);
			if(!$all_tags)continue;

			foreach($all_tags as $tag){
				if(stripos($tag, '&')){
					$condition = explode('&', $tag);
					$hit = true;
					foreach($condition as $t){
						if(stripos($name, $t)===false){
							$hit = false;
						}
					}
					if($hit)$match_tags[$subcat][] = r('&', '', $tag);

				}elseif(stripos($name, $tag)!==false){
					$match_tags[$subcat][] = $tag;
				}
			}
			if(isset($match_tags[$subcat]))$match_tags[$subcat] = array_unique($match_tags[$subcat]);
		}

		if($match_tags){

			$this->updatePromoTag($sp, $goods_id, $match_tags);
			return $match_tags;
		}else{
			$this->deletePromo2tag($sp, $goods_id);
		}
	}

	//更新特卖标签
	function updatePromoTag($sp, $goods_id, $new_tags){

		if(!$sp || !$goods_id || !$new_tags)return;

		//同步promo标签
		$promo = $this->promoDetail($sp, $goods_id);

		if($promo){
			$promo2tag = $this->db('promotion.queue_promo2tag')->get($sp, $goods_id);

			if($promo2tag && count($promo2tag) == count($new_tags)){
				$promo2tag_tag = array();
				foreach($promo2tag as $subcat => $tags){
					foreach($tags as $tag){
						$promo2tag_tag[$subcat][$tag] = 1;
					}
				}

				foreach($new_tags as $subcat => $tags){
					foreach($tags as $tag){
						if(isset($promo2tag_tag[$subcat][$tag])){
							unset($promo2tag_tag[$subcat][$tag]);
						}
					}
				}

				foreach($promo2tag_tag as $subcat => $tags){
					if(!$tags)unset($promo2tag_tag[$subcat]);
				}

				//无需删除标签
				if(!count($promo2tag_tag)){
					return true;
				}
			}

			$this->deletePromo2tag($sp, $goods_id);

			foreach($new_tags as $subcat => $tags){
				foreach($tags as $tag){
					$ret = $this->db('promotion.queue_promo2tag')->add(array('sp'=>$sp,'goods_id'=>$goods_id,'cat'=>$this->subcat2cat($subcat),'subcat'=>$subcat ,'tag'=>$tag,'type'=>$promo['type'],'createtime'=>$promo['createtime']));
				}
			}

			$this->updateGoods($sp, $goods_id, array('tags'=>serialize($new_tags)));
			$this->clearCache($sp, $goods_id);
		}

		return $ret;
	}

	//增加特卖标签
	function addGoodsTag($sp, $goods_id, $new_tags=array()){

		if(!$sp || !$goods_id || !$new_tags)return;
		$promo = $this->promoDetail($sp, $goods_id);
		$goods = $this->goodsDetail($sp, $goods_id);

		$old_tags = (array)$goods['tags'];

		foreach($new_tags as $subcat => $tags){
			foreach($tags as $tag){
				$old_tags[$subcat] = $tag;
			}
		}

		$this->updateGoods($sp, $goods_id, array('tags'=>serialize($old_tags)));

		foreach($new_tags as $subcat => $tags){
			foreach($tags as $i_tag){
				$ret = $this->db('promotion.queue_promo2tag')->add(array('sp'=>$sp,'goods_id'=>$goods_id,'cat'=>$this->subcat2cat($subcat),'subcat'=>$subcat ,'tag'=>$i_tag,'type'=>$promo['type'],'createtime'=>$promo['createtime']));
			}
		}
		return $ret;
	}

	//清空特卖标签
	function deletePromo2tag($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$this->db('promotion.review')->delete($sp, $goods_id);
		$this->db('promotion.queue_promo2tag')->delete($sp, $goods_id);
	}

	//清空特卖分类索引
	function deletePromo2cat($sp, $goods_id, $subcat=''){

		if(!$sp || !$goods_id)return;
		$this->db('promotion.review')->delete($sp, $goods_id);
		$this->db('promotion.queue_promo2cat')->delete($sp, $goods_id, $subcat);
	}

	//清空子分类外的特卖分类索引
	function deletePromo2catNotIn($sp, $goods_id, $subcat){

		if(!$sp || !$goods_id || !$subcat)return;
		$this->db('promotion.queue_promo2tag')->deleteNotIn($sp, $goods_id, $subcat);
	}

	//删除特卖
	function deletePromo($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$this->db('promotion.queue_promo')->delete($sp, $goods_id);
		$this->deletePromo2cat($sp, $goods_id);
		$this->deletePromo2tag($sp, $goods_id);
	}

	//清空商品、特卖分类
	function clearGoodsCat($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$ret = $this->db('promotion.goods')->update($sp, $goods_id, array('cat'=>'', 'subcat'=>''));
		$this->db('promotion.queue_promo')->update($sp, $goods_id, array('cat_assign'=>0));
		$this->deletePromo2cat($sp, $goods_id);
		$this->clearCache($sp, $goods_id);
	}

	//清除特卖商品信息缓存
	function clearCache($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$key1 = 'goods:detail:sp:'.$sp.':goods_id:'.$goods_id;
		$key2 = 'promo:detail:sp:'.$sp.':goods_id:'.$goods_id;
		D('cache')->clean($key1);
		D('cache')->clean($key2);
	}

	/**
	 * 新增商品
	 * @return bool         是否新增成功
	 */
	function importGoods($sp, $url_tpl, $url_id, $data_from, $data_category='', $fix_field=array()){

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
			if(taobaoSp($sp)){

				$goods_detail = $this->api('taobao')->getItemDetailByServer($url_id);
				if(!$goods_detail)return false;
				$data['name'] = $goods_detail['p_title'];
				$data['price_now'] = $goods_detail['p_price'];
				$data['price_max'] = $goods_detail['p_price_avg'];
				$data['pic_url'] = $goods_detail['p_pic_url'];
			}else{
				$goods_detail = $this->goodsDetailByUrl($url);
				if(!$goods_detail)return false;
				$data['name'] = $goods_detail['name'];
				$data['price_now'] = $goods_detail['price_now'];
				$data['pic_url'] = $goods_detail['pic_url'];
			}

			$data = array_merge($data, $fix_field);
			$goods_id = $this->db('promotion.goods')->add($data['sp'], arrayClean($data));

			if($goods_id){
				$this->redis('promotion')->goodsCounter($data['sp']);
			}
		}

		//标识访问过该商品，等待价格抓取
		if($goods_id){
			//更新visit window，保持price_detect只追踪一段时间内的商品价格
			$this->db('promotion.queue_visit')->visited($data['sp'], $goods_id);
			//标识商品被导入次数，用来发现新热点
			$this->redis('promotion')->saleCounter($sp, $goods_id);
			//更新了销量，触发重新计算促销商品权重
			//暂时不再更新权重
			//D('weight')->update($sp, $goods_id);

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

		return $this->db('promotion.queue_visit')->getLastVisit(90);
	}

	//更新商品信息
	function updateGoods($sp, $goods_id, $new_data){

		if(!$sp || !$goods_id || !$new_data)return;
		$ret = $this->db('promotion.goods')->update($sp, $goods_id, $new_data);
		if($ret){
			$this->clearCache($sp, $goods_id);
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

	//标记该商品是降价商品
	function markPromoDiscount($sp, $goods_id, $price_avg, $price_now, $hd_expire, &$hit_cat=false){

		if(!$sp || !$goods_id || !$price_avg || !$price_now)return;
		$promo = $this->promoDetail($sp, $goods_id);
		if(!$promo){

			$status = \DB\QueuePromo::STATUS_NORMAL;
			$detail = $this->goodsDetail($sp, $goods_id);
			$ret = $this->db('promotion.queue_promo')->add(array('status'=>$status, 'sp'=>$sp, 'goods_id'=>$goods_id, 'price_avg'=>$price_avg, 'price_now'=>$price_now, 'hd_expire'=>$hd_expire, 'type'=>\DB\QueuePromo::TYPE_DISCOUNT));
			//自动匹配分类，快速覆盖新增特卖，re_match脚本做全量更新同步分类规则变化
			$match_cats = $this->matchGoodsCat($sp, $goods_id);

			if($match_cats){
				$hit_cat = $match_cats;
			}else{
				//加入待审核列表
				$this->db('promotion.review')->add(\DB\Review::TYPE_PROMO, array('sp'=>$sp, 'goods_id'=>$goods_id));
			}

			//加入搜索索引，快速覆盖新增特卖，rebuild_index脚本做全量更新，防止有商品上下线
			D('search')->buildIndex($sp, $goods_id);
			$this->redis('promotion')->promoCounter($sp, $goods_id);

			return true;
		}else{
			//已经导过不再更新分类
			$hit_cat = array('existed');
		}
	}

	//标记该商品是活动商品(新增返回true)
	function markPromoHuodong($sp, $goods_id, $price_avg, $price_now, $album_id, $hd_begin='', $hd_expire=''){

		if(!$sp || !$goods_id || !$price_avg || !$price_now || !$album_id)return;
		if(!$this->promoDetail($sp, $goods_id)){

			$status = \DB\QueuePromo::STATUS_NORMAL;
			$detail = $this->goodsDetail($sp, $goods_id);

			$ret = $this->db('promotion.queue_promo')->add(array('status'=>$status, 'sp'=>$sp, 'goods_id'=>$goods_id, 'price_avg'=>$price_avg, 'price_now'=>$price_now, 'album_id'=>$album_id, 'hd_begin'=>$hd_begin, 'hd_expire'=>$hd_expire, 'type'=>\DB\QueuePromo::TYPE_HUODONG));
			//自动匹配分类，快速覆盖新增特卖，re_match脚本做全量更新同步分类规则变化
			$this->matchGoodsCat($sp, $goods_id);

			//加入待审核列表
			$this->db('promotion.review')->add(\DB\Review::TYPE_PROMO, array('sp'=>$sp, 'goods_id'=>$goods_id));
			//加入搜索索引，快速覆盖新增特卖，rebuild_index脚本做全量更新，防止有商品上下线
			D('search')->buildIndex($sp, $goods_id);
			if($ret)$this->redis('promotion')->promoCounter($sp, $goods_id);
			return true;
		}
	}

	//标记该商品是降价商品
	function markPromoGuang($sp, $goods_id, $price_avg, $price_now, $fix_name='', $force_subcat='', $force_tag=''){

		if(!$sp || !$goods_id || !$price_avg || !$price_now ||!$fix_name)return;
		$promo = $this->promoDetail($sp, $goods_id);
		if(!$promo){

			$status = \DB\QueuePromo::STATUS_NORMAL;
			$detail = $this->goodsDetail($sp, $goods_id);
			$ret = $this->db('promotion.queue_promo')->add(array('status'=>$status, 'sp'=>$sp, 'goods_id'=>$goods_id, 'price_avg'=>$price_avg, 'price_now'=>$price_now, 'type'=>\DB\QueuePromo::TYPE_JIE));
			//自动匹配分类，快速覆盖新增特卖，re_match脚本做全量更新同步分类规则变化
			$this->matchGoodsCat($sp, $goods_id, $fix_name);
			if($force_subcat){
				$this->addGoodsSubcat($sp, $goods_id, $force_subcat);
				$this->markPromoHasCat($sp, $goods_id);
			}

			//加入待审核列表
			$this->db('promotion.review')->add(\DB\Review::TYPE_JIE, array('sp'=>$sp, 'goods_id'=>$goods_id));

			//加入搜索索引，快速覆盖新增特卖，rebuild_index脚本做全量更新，防止有商品上下线
			D('search')->buildIndex($sp, $goods_id);
			$this->redis('promotion')->promoCounter($sp, $goods_id);

			return true;
		}

		$this->matchGoodsTag($sp, $goods_id);
		if($force_tag && $force_subcat)$this->addGoodsTag($sp, $goods_id, array($force_subcat=>array($force_tag)));
	}

	//标记该商品是9块9商品(新增返回true)
	function markPromo9($sp, $goods_id, $price_avg, $price_now, &$hit_cat = false){

		if(!$sp || !$goods_id || !$price_avg || !$price_now)return;

		if(!$this->promoDetail($sp, $goods_id)){

			$status = \DB\QueuePromo::STATUS_NORMAL;
			$detail = $this->goodsDetail($sp, $goods_id);

			$ret = $this->db('promotion.queue_promo')->add(array('status'=>$status, 'sp'=>$sp, 'goods_id'=>$goods_id, 'price_avg'=>$price_avg, 'price_now'=>$price_now, 'type'=>\DB\QueuePromo::TYPE_9));
			//只有匹配不到分类才进入审核
			$match_cats = D('promotion')->matchGoodsCat($sp, $goods_id);

			if(!$match_cats){
				$this->db('promotion.review')->add(\DB\Review::TYPE_9, array('sp'=>$sp, 'goods_id'=>$goods_id));
			}else{
				$hit_cat = $match_cats;
			}
			//加入搜索索引，快速覆盖新增特卖，rebuild_index脚本做全量更新，防止有商品上下线
			D('search')->buildIndex($sp, $goods_id);
			return true;
		}else{
			//已经导过不再更新分类
			$hit_cat = array('existed');
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

		$this->db('promotion.queue_promo');
		$key = 'promo:detail:sp:'.$sp.':goods_id:'.$goods_id;
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		$promo = $this->db('promotion.queue_promo')->find(array('sp'=>$sp, 'goods_id'=>$goods_id));
		if(!$promo)return;
		$promo = clearTableName($promo);
		//计算无效状态
		$invalid = false;
		$goods_detail = $this->goodsDetail($sp, $goods_id);

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
		D('cache')->set($key, $promo, MINUTE*10, false);
		//$d[$sp][$goods_id] = $promo;
		return $promo;
	}

	//更新特卖信息
	function updatePromo($sp, $goods_id, $new_data){

		if(!$sp || !$goods_id || !$new_data)return;
		$ret = $this->db('promotion.queue_promo')->update($sp, $goods_id, $new_data);
		if($ret){
			$this->clearCache($sp, $goods_id);
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
		if($cache){
			return D('cache')->ret($cache);
		}

		$condition = arrayClean($cat_condition);
		static $huodong_repeat=array();

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

		$condition_str = join(' AND ', $condition_str);
		$result = $this->db('promotion.queue_promo2cat')->findAll($condition_str, 'DISTINCT sp, goods_id, type', 'ORDER BY weight DESC, id DESC', $show, @$_GET['page']?$_GET['page']:1);
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
		D('cache')->set($key, $result, MINUTE*10);

		return $result;
	}

	/**
	 * 获取标签商品列表
	 * @param  [type]  $pn            [description]
	 * @param  array   $cat_condition 分类搜索条件(cat/subcat)
	 * @param  integer $show          [description]
	 * @param  integer $maxPages      [description]
	 * @return [type]                 [description]
	 */
	function getTagList($pn, $cat_condition=array(), $show = 3, $exact=true, $price_limit=180) {

		$key = 'promo:get_tag_list:cond:'.md5(serialize($cat_condition)).':show:'.$show.':page:'.intval(@$_GET['page']);
		$cache = D('cache')->get($key);
		if($cache){
			return D('cache')->ret($cache);
		}

		$condition = arrayClean($cat_condition);
		static $huodong_repeat=array();

		//最多加载20页
		if(isset($_GET['page']) && $_GET['page'] > C('comm', 'promo_cat_max_page'))
			return false;

		$condition_str = array();

		foreach($condition as $field => $c){

			if(is_array($c)){
				$condition_str[] = "{$field} in ('" . join("','", $c) . "')";
			}else {
				$condition_str[] = "{$field} = '{$c}'";
			}

			$having = '';
			if($field == 'tag'){

				//多个标签，必须都命中，使用having
				if(count($c) > 1 && $exact){
					$having = 'HAVING nu = '.count($c);
				}
			}
		}

		$condition_str = 'WHERE '.join(' AND ', $condition_str);
		$page_start = $show * intval(@$_GET['page']);

		$result = $this->db('promotion.queue_promo2cat')->query("SELECT count(*) nu, sp, goods_id, id, type FROM duosq_promotion.queue_promo2tag {$condition_str} GROUP BY sp,goods_id {$having} ORDER BY id DESC LIMIT {$page_start}, ".($show+3));
		$result = clearTableName($result);
		if(!$result)$result = array();

		$this->db('promotion.queue_promo');

		$result = $this->renderPromoDetail($result, $price_limit);
		if($result)$result = array_slice($result, 0, $show);
		D('cache')->set($key, $result, MINUTE*10);

		return $result;
	}

	//渲染特卖信息
	function renderPromoDetail($result, $price_limit = 200){

		if(!$result)return;
		$new_ret = array();
		$this->db('promotion.goods');
		$this->db('promotion.queue_promo');
		foreach ($result as $ret) {

			$goods_detail = $this->goodsDetail($ret['sp'], $ret['goods_id']);
			if(!$goods_detail)continue;

			$promo_detail = $this->promoDetail($ret['sp'], $ret['goods_id']);
			//过滤需隐藏的特卖
			if(!$promo_detail || $promo_detail['status'] != \DB\QueuePromo::STATUS_NORMAL){
				$goods_detail['goods_id'] = $goods_detail['id'];
				$new_ret[] = $goods_detail;
				continue;
			}

			//不显示超过200元的特卖
			if($promo_detail['price_now'] > $price_limit){
				continue;
			}

			$tmp = $promo_detail;

			$saled = $this->redis('promotion')->getSaleCount($ret['sp'], $ret['goods_id']);
			$saled = $saled + C('comm', 'promo_import_goods_sales_min');
			if(iSp($ret['sp'])){
				$saled = $saled * 10 + $ret['goods_id']%50;
			}else{
				$saled = $saled * 25 + $ret['goods_id']%50;
			}
			$tmp['week_sales'] = $saled;
			$tmp['hd_content'] = strip_tags($tmp['hd_content']);

			if(time() - strtotime($promo_detail['createdate']) < DAY){
				$tmp['is_new'] = true;
			}
			//融合商品信息
			$tmp['name'] = $goods_detail['name'];
			$tmp['name_short'] = $goods_detail['name_short'];
			$tmp['recommend'] = $goods_detail['recommend'];
			$tmp['tags'] = $goods_detail['tags'];
			$tmp['pic_url'] = $goods_detail['pic_url_cover']?$goods_detail['pic_url_cover']:$goods_detail['pic_url'];
			$tmp['pic_url_orig'] = $goods_detail['pic_url'];
			$tmp['cat'] = $goods_detail['cat'];
			$tmp['saled'] = $goods_detail['saled'];
			$tmp['url_tpl'] = $goods_detail['url_tpl'];
			$tmp['url_id'] = $goods_detail['url_id'];
			$new_ret[] = $tmp;
		}

		return $new_ret;
	}

	//获取经过人工修正后的当前价格
	function getFixNowPrice($sp, $goods_id){

		if(!$sp || !$goods_id)return;
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

		$new_suggest = $this->api('taobao')->getSuggest($keyword, 15);

		/*不再入库匹配
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
		*/

		D('cache')->set($key, $new_suggest, DAY*10, true);
		return $new_suggest;
	}

	//获取一个随机促销
	function getRandPromo(){

		return $this->db('promotion.queue_promo2cat')->find("subcat<>'' AND brand_id<>'' AND createtime>'".date('Y-m-d', time()-DAY*300)."'", '', 'rand()');
	}

	//获取所有特卖
	function getAllPromo(){

		$ret = $this->db('promotion.queue_promo')->findAll(array('sp'=>'<> taobao'),'sp,goods_id');
		return clearTableName($ret);
	}

	//获取特卖权重
	function getPromoWeight($sp, $goods_id){

		if(!$sp || !$goods_id)return;

		$key = 'promotion:weight:sp:'.$sp.'goods_id:'.$goods_id;
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		$weight = $this->db('promotion.queue_promo2cat')->field('weight', array('sp'=>$sp, 'goods_id'=>$goods_id));
		D('cache')->set($key, $weight, HOUR, true);

		return $weight;
	}

	//从接口更新商品长名称、评论、简介
	function updateGoodsDeepInfo($sp, $goods_id, $num_iid){

		if(!$sp || !$goods_id || !$num_iid)return;
		//加锁，每天同个商品更新1次
		if(!D()->redis('lock')->getlock(\Redis\Lock::LOCK_GET_TAOBAO_ITEM_DEEP_INFO, $num_iid))return;

		$new = array();
		if(taobaoSp($sp)){
			$info = $this->api('taobao')->getItemAllDetail($num_iid);
			if($info){
				if($info['comment'])
					$new = array('shop_id'=>$info['shop_id'], 'intro'=>$info['intro'], 'comment'=>$info['comment']);
				else
					$new = array('shop_id'=>$info['shop_id'], 'intro'=>$info['intro']);
				$this->updateGoods($sp, $goods_id, $new);
			}
		}
		$this->sendGoodsDeepInfoFetchedMsg($sp, $goods_id);
		return $new;
	}

	/**
	 * 发送淘宝商品详情入库消息
	 * @param  int    $sp    商城号
	 * @param  [type] $goods_id    商品编号
	 * @return bool               是否发送成功
	 */
	function sendGoodsDeepInfoFetchedMsg($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		return D()->redis('queue')->add(\REDIS\Queue::KEY_GOODS_DEEP_INFO_FETCHED, $sp.':'.$goods_id);
	}

	/**
	 * 获取淘宝商品详情入库消息
	 * @return array
	 */
	function getGoodsDeepInfoFetchedMsg(){

		$msg = D()->redis('queue')->bget(\REDIS\Queue::KEY_GOODS_DEEP_INFO_FETCHED);
		if(!$msg)return;
		list($sp, $goods_id) = explode(':', $msg);
		return array('sp'=>$sp, 'goods_id'=>$goods_id);
	}

	/**
	 * 完成淘宝商品详情入库消息触发的任务
	 * @param  int    $sp    商城号
	 * @param  [type] $goods_id    商品编号
	 */
	function doneGoodsDeepInfoFetchedMsg($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		return D()->redis('queue')->done(\REDIS\Queue::KEY_GOODS_DEEP_INFO_FETCHED, $sp.':'.$goods_id);
	}
}
?>