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
		clearTableName($cache);

		if ($cache){
			if ($cache['content'])
				return json_decode($cache['content'], true);
			return false;//商品无返利
		}

		//任务链接永远有返利
		if($param == C('comm', 'newgift_task_pid')){
			$detail = array('p_title'=>'多省钱(duosq.com)粘贴网址开启红包专用商品', 'p_seller'=>'多省钱官方店', 'p_price'=>'10.00', 'p_pic_url'=>'http://www.duosq.com/img/avatar.png', 'has_fanli'=>1, 'key'=>'newgift_task_pid');
		}else{
			$detail = $this->api($sp)->getItemDetail($param);
		}

		if (!isset($detail['errcode'])) {

			//修正有返利，但不允许返利的淘宝商品
			if($sp=='taobao' && !$is_tmall && $detail['has_fanli']){
				if(!$this->api('taobao')->isRebateAuth($param)){
					$detail['has_fanli'] = 0;
					D('log')->action(1002, 1, array('data1'=>$param));
				}
			}

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

	//从缓存中判断商品是否天猫商品
	function isTmall($param){

		$log_obj = $this->db('cache_api_item');
		$cache = $log_obj->find(array('sp' => 'taobao', 'p_id' => $param), '', 'id DESC');
		clearTableName($cache);
		if($cache){
			return $cache['is_tmall'];
		}else{
			return false;
		}
	}
}
?>