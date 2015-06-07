<?php
//DAL:商品信息访问模块
namespace DAL;

class Item extends _Dal {

	//读取商品详情
	//is_tmall用于标识是否天猫商品，便于跟单进行天猫补贴
	function detail($sp, $param, $is_tmall=0) {

		if (!$sp || !$param) return false;
		$log_obj = $this->db('cache_api_item');
		$cache = $log_obj->find(array('sp' => $sp, 'p_id' => $param, 'createdate' => date('Y-m-d')));
		$cache = clearTableName($cache);

		if ($cache){
			if(!$cache['content'])return;
			$cache['content'] = json_decode($cache['content'], true);
			$cache['content']['p_seller'] = $cache['seller'];
			return $cache['content'];
		}

		//任务链接永远有返利
		if($param == C('comm', 'newgift_task_pid')){
			$detail = array('p_title'=>'多省钱(duosq.com)粘贴网址开启红包专用商品', 'p_seller'=>'多省钱官方店', 'p_price'=>'10.00', 'p_pic_url'=>'http://static.duosq.com/img/avatar/default.png', 'has_fanli'=>1, 'key'=>'newgift_task_pid');
		}else{
			$detail = $this->api($sp)->getItemDetail($param);
		}

		if (!isset($detail['errcode'])) {

			if(isset($detail['is_tmall']) && $detail['is_tmall'])$is_tmall=1;
			//修正有返利，但不允许返利的淘宝商品
			/*
			if($sp=='taobao' && !$is_tmall && $detail['has_fanli']){
				if(!$this->api('taobao')->isRebateAuth($param)){
					$detail['has_fanli'] = 0;
					D('log')->action(1002, 1, array('data1'=>$param));
				}
			}
			*/

			if (@$detail['has_fanli']) {
				$detail['is_tmall'] = $is_tmall;
				$log_obj->add(1, $sp, 'succ', $param, $detail, $detail['key']);
			} else {
				//暂不缓存无返利结果，以免alimama错误，导致当天大面积无返利
				//$log_obj->add(1, $sp, 'no_rebate', $param, '', $detail['key']);
			}
			return $detail;
		} else {
			$log_obj->add(0, $sp, $this->api($sp)->err_code, '', '', $detail['key']);
		}
	}

	//从Tae接口读取商品详情
	function detailTae($iid){

		if(!$iid)return;

		$key = 'taobao:tae:item:detail:iid:'.$iid;
		$cache = D('cache')->get($key);
		if($cache){
			return D('cache')->ret($cache);
		}

		$tae_detail = D()->api('taobao')->getItemDetailByTae($iid);
		D('cache')->set($key, $tae_detail, MINUTE*10, true);

		return $tae_detail;
	}

	//从缓存中判断商品是否天猫商品
	function isTmall($wangwang){

		if(!$wangwang)return;

		$key = 'taobao:wangwang:'.md5($wangwang).'is_tmall';
		$cache = D('cache')->get($key);
		if($cache){
			return D('cache')->ret($cache);
		}

		$log_obj = $this->db('cache_api_item');
		$hit = $log_obj->find(array('sp' => 'taobao', 'seller' => $wangwang, 'is_tmall'=>1));
		$hit = clearTableName($hit);

		if($hit){
			$is_tmall = $cache['is_tmall']?1:0;
		}else{
			$is_tmall = 0;
		}

		D('cache')->set($key, $is_tmall, MINUTE*30, true);
	}

	//判断指定商品是不是天猫
	function isTmallById($item_id){

		if(!$item_id)return;

		$key = 'is_tmall:id:'.$item_id;
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);
		$header = httpHeader("http://item.taobao.com/item.htm?id={$item_id}");

		if(!stripos($header, '1.1 200') && !stripos($header, '1.1 301') && !stripos($header, '1.1 302')){
			return false;
		}

		if(stripos($header, '1.1 301') || stripos($header, '1.1 302')){
			D('cache')->set($key, 1, WEEK);
			return 1;
		}else{
			D('cache')->set($key, 0, WEEK);
			return 0;
		}
	}
}
?>