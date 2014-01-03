<?php
//DAL:商品信息访问模块
namespace DAL;

class Item extends _Dal {

	//读取商品详情
	function detail($sp, $param) {

		if (!$sp || !$param) return false;
		$log_obj = $this->db('cache_api_item');
		$cache = $log_obj->find(array('sp' => $sp, 'p_id' => $param, 'created' => date('Y-m-d')));
		clearTableName($cache);

		if ($cache){
			if ($cache['content'])
				return json_decode($cache['content'], true);
			return false;//商品无返利
		}
		$detail = $this->api($sp)->getItemDetail($param);

		if (!isset($detail['errcode'])) {
			if (@$detail['has_fanli']) {
				$log_obj->add(1, $sp, 'succ', $param, json_encode($detail), $detail['key']);
			} else {
				//暂不缓存无返利结果，以免alimama错误，导致当天大面积无返利
				//$log_obj->add(1, $sp, 'no_rebate', $param, '', $detail['key']);
			}
			return $detail;
		} else {
			$log_obj->add(0, $sp, $this->api($sp)->err_code, '', '', $detail['key']);
		}
	}
}
?>