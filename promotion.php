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
	function buildUrl($url_tpl, $url_id){

		if(!$url_tpl || !$url_id)return;
		$rule = C('rule_goods_url', $url_tpl);
		$url = preg_replace('/{.+?}/', $url_id, $rule['tpl']);
		return $url;
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

		static $config;
		if($all)
			$all=1;
		else
			$all=0;

		if(isset($config[$all]))return $config[$all];

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

		$config[$all] = $ret;
		return $ret;
	}

	//从商品的子分类找出父分类
	function subcat2Cat($subcat){

		$cat_config = $this->getCatConfig();
		foreach($cat_config as $c_cat => $c_subcats){
			foreach($c_subcats as $c_subcat){
				if($c_subcat == $subcat){
					return $c_cat;
				}
			}
		}
		return '';
	}

	//获取商品分类匹配规则
	function getCatRules($cat='', $subcat=''){

		return $this->redis('keys')->goodsCatRules($cat, $subcat);
	}

	//设置商品分类匹配规则
	function setCatRules($cat, $subcat, $rules){

		if(!$cat || !$subcat || !$rules)return;
		$this->redis('keys')->goodsCatRules($cat, $subcat, $rules);
		return true;
	}

	//获取商品详情
	function goodsDetail($sp, $goods_id){

		$detail = $this->db('promotion.goods')->detail($sp, $goods_id);
		if($detail){
			$detail['url'] = $this->buildUrl($detail['url_tpl'], $detail['url_id']);
			if($detail['cat'])$detail['cat'] = explode('|', $detail['cat']);
			if($detail['subcat'])$detail['subcat'] = explode('|', $detail['subcat']);
		}

		return $detail;
	}

	//匹配并更新商品分类信息
	function matchGoodsCat($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$detail = $this->goodsDetail($sp, $goods_id);

		$name = $detail['name'];
		$all_rules = $this->getCatRules();

		$match_subcat = array();

		foreach($all_rules as $cat => $val){
			foreach($val as $subcat => $rules){
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
						if(preg_match("/{$rule}/i", $name)){
							$match_subcat[$subcat] = 1;
						}
					}
				}
			}
		}

		if($match_subcat){
			$old_subcat = $detail['subcat'];
			if($old_subcat){
				foreach($old_subcat as $subcat){
					$match_subcat[$subcat] = 1;
				}
			}
			$match_subcat = array_keys($match_subcat);
			return $this->updateGoodsCat($sp, $goods_id, $match_subcat);
		}
	}

	//更新商品分类
	function updateGoodsCat($sp, $goods_id, $subcats){

		if(!$sp || !$goods_id || !$subcats)return;
		$match_cats = array();
		foreach($subcats as $subcat){
			if($cat = $this->subcat2Cat($subcat)){
				$match_cats[$cat] = 1;
			}
		}

		$cat = array();
		if($match_cats){
			$cats = array_slice(array_keys($match_cats), 0, 5);
			$cat_str = join('|', $cats);
		}
		$subcat_str = join('|', array_slice($subcats, 0, 10));

		$ret = $this->db('promotion.goods')->update($sp, $goods_id, array('cat'=>$cat_str, 'subcat'=>$subcat_str));

		if(!$ret)return false;

		//同步promo分类
		$promo = $this->promoDetail($sp, $goods_id);

		if($promo){
			$promo2cat = $this->db('promotion.queue_promo2cat')->findAll(array('sp'=>$sp, 'goods_id'=>$goods_id));
			clearTableName($promo2cat);
			if($promo2cat && count($promo2cat) == count($subcats)){
				$promo2cat_subcat = array();
				foreach($promo2cat as $p){
					$promo2cat_subcat[$p['subcat']] = 1;
				}
				foreach($subcats as $subcat){
					unset($promo2cat_subcat[$subcat]);
				}

				if(!count($promo2cat_subcat))return $ret;
			}

			$this->db('promotion.queue_promo2cat')->query("DELETE FROM queue_promo2cat WHERE sp = '{$sp}' AND goods_id = '{$goods_id}'");

			foreach($subcats as $subcat){
				$this->db('promotion.queue_promo2cat')->create();
				$this->db('promotion.queue_promo2cat')->save(array('sp'=>$sp,'goods_id'=>$goods_id,'cat'=>$this->subcat2Cat($subcat),'subcat'=>$subcat,'type'=>$promo['type'],'createtime'=>$promo['createtime']));
			}

			$this->db('promotion.queue_promo')->update($sp, $goods_id, array('cat_assign'=>1));
		}

		return $ret;
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
		}

		//标识访问过该商品，等待价格抓取
		if($goods_id){
			$this->db('promotion.queue_visit')->visited($data['sp'], $goods_id);
			//标识商品被导入次数，用来发现新热点
			$this->redis('promotion')->saleCounter($sp, $goods_id);
		}

		return $goods_id;
	}

	//获取最近被访问过的商品
	function getLastVisitGoods(){

		return $this->db('promotion.queue_visit')->getLastVisit(30);
	}

	//更新商品价格走势
	function updateGoodsPrice($sp, $goods_id, $price_trend, $price_min, $price_max, $price_now){

		if(!$sp || !$goods_id)return;
		$ret = $this->db('promotion.goods')->update($sp, $goods_id, array('price_trend'=>$price_trend, 'price_min'=>$price_min, 'price_max'=>$price_max, 'price_now'=>$price_now, 'price_update'=>date('Y-m-d')));

		if($ret){
			$this->db('promotion.queue_visit')->detected($sp, $goods_id);
			return $ret;
		}
	}

	//更新商品信息
	function updateGoodsStatus($sp, $goods_id, $status){

		if(!$sp || !$goods_id)return;

		return $this->db('promotion.goods')->update($sp, $goods_id, array('status'=>$status));
	}

	//标记该商品是降价商品(type 1:特卖 2:热卖)
	function markPromoDiscount($sp, $goods_id, $price_avg, $price_now){

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
			$this->db('promotion.queue_promo')->add(array('status'=>$status, 'sp'=>$sp, 'goods_id'=>$goods_id, 'cat'=>$detail['cat'], 'subcat'=>$detail['subcat'], 'price_avg'=>$price_avg, 'price_now'=>$price_now, 'type'=>\DB\QueuePromo::TYPE_DISCOUNT));
			return true;
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
			$this->db('promotion.queue_promo')->add(array('status'=>$status, 'sp'=>$sp, 'goods_id'=>$goods_id, 'cat'=>$detail['cat'], 'subcat'=>$detail['subcat'], 'price_avg'=>$price_avg, 'price_now'=>$price_now, 'hd_content'=>$hd_content, 'hd_begin'=>$hd_begin, 'hd_expire'=>$hd_begin, 'type'=>\DB\QueuePromo::TYPE_HUODONG));
			return true;
		}
	}

	//获取特卖详情
	function promoDetail($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$promo = $this->db('promotion.queue_promo')->find(array('sp'=>$sp, 'goods_id'=>$goods_id));
		return clearTableName($promo);
	}

	//更新特卖信息
	function updatePromo($sp, $goods_id, $new_data){

		if(!$sp || !$goods_id || !$new_data)return;
		return $this->db('promotion.queue_promo')->update($sp, $goods_id, $new_data);
	}

	/**
	 * 获取特卖商品列表
	 * @param  [type]  $pn            [description]
	 * @param  array   $cat_condition 分类搜索条件(cat/subcat)
	 * @param  integer $show          [description]
	 * @param  integer $maxPages      [description]
	 * @return [type]                 [description]
	 */
	function getList($pn, $cat_condition=array(), $show = 3, $maxPages = 10) {

		$condition = arrayClean($cat_condition);
		$condition['sp'] = '<> taobao';
		static $huodong_repeat=array();

		//page = 0 返回总页数
		$pn->show = $show;
		$pn->sortBy = 'weight';
		$pn->direction = 'desc';
		$pn->maxPages = $maxPages;

		//最多加载20页
		if(isset($_GET['page']) && $_GET['page'] > C('comm', 'promo_cat_max_page'))
			return false;

		list($order, $limit, $page) = $pn->init($condition, array('modelClass' => $this->db('promotion.queue_promo2cat')));

		$result = $this->db('promotion.queue_promo2cat')->findAll($condition, '', 'GROUP BY sp, goods_id ORDER BY ' . $order, $limit+5, $page);
		clearTableName($result);
		if(!$result)$result = array();

		$this->db('promotion.queue_promo');

		if(!isset($_GET['page'])){

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

			$result_hodong = $this->db('promotion.queue_promo2cat')->findAll($condition, '', 'GROUP BY sp,goods_id ORDER BY createtime DESC', 3);
			clearTableName($result_hodong);

			if($result_hodong){
				foreach($result_hodong as $huodong){
					$huodong_repeat[$huodong['goods_id']] = 1;
					array_unshift($result, $huodong);
				}
			}
		}

		$result = $this->_renderPromoDetail($result);

		return array_slice($result, 0, $show+3);
	}

	//渲染特卖信息
	private function _renderPromoDetail($result){

		$new_ret = array();
		foreach ($result as $ret) {

			$promo_detail = $this->promoDetail($ret['sp'], $ret['goods_id']);
			if(!$promo_detail || $promo_detail['status'] != \DB\QueuePromo::STATUS_NORMAL)continue;

			$goods_detail = $this->goodsDetail($ret['sp'], $ret['goods_id']);
			if(!$goods_detail)continue;

			$valid = true;
			if($promo_detail == \DB\QueuePromo::TYPE_DISCOUNT){
				if($goods_detail['price_now'] > $promo_detail['price_now']){
					$valid = false;
				}
			}

			if($promo_detail == \DB\QueuePromo::TYPE_HUODONG){
				if(strtotime($promo_detail['hd_expire']) > 0 && strtotime($promo_detail['hd_expire']) < time()){
					$valid = false;
				}
			}
			$tmp = $promo_detail;
			if($tmp['hd_content']){
				$tmp['hd_content'] = r(array("\r", "\n",'	','&nbsp;'), '', $tmp['hd_content']);
				$tmp['hd_content'] = strip_tags(nl2br($tmp['hd_content']));
				$tmp['hd_content'] = preg_replace('/[\\x20]+/', ' ', $tmp['hd_content']);
				$tmp['hd_content'] = '&nbsp; &nbsp; &nbsp;' . $tmp['hd_content'];
				$tmp['hd_content'] = r(array('meidebi','没得比'), array('dousq','多省钱'), $tmp['hd_content']);
			}else{
				$saled = $this->redis('promotion')->getSaleCount($ret['sp'], $ret['goods_id']);
				if($saled > 10){
					$saled = $saled * 100;
					$saled = "上周原价热销：<font class=blue>{$saled}</font>件<br />";
				}
				$tmp['hd_content'] = '90天均价：¥'.price_yuan($promo_detail['price_avg']).'<br />'.$saled.'刚刚降至：<font class=orange>¥'.price_yuan($promo_detail['price_now']).'</font>，现在出手直接省掉了<font class=green>'.rate_diff($promo_detail['price_now'], $promo_detail['price_avg']).'%</font>哟~';
			}

			$tmp['name'] = $goods_detail['name'];
			$tmp['pic_url'] = $goods_detail['pic_url'];
			$tmp['url_tpl'] = $goods_detail['url_tpl'];
			$tmp['url_id'] = $goods_detail['url_id'];
			$tmp['valid'] = $valid;

			$new_ret[] = $tmp;
		}

		return $new_ret;
	}
}
?>