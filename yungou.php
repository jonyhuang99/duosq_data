<?php
//DAL:云购商品处理模块
namespace DAL;

class Yungou extends _Dal {

	//云购商品状态
	const STATUS_WAIT_OPEN = 0; //状态_待开放
	const STATUS_OPENING = 1; //状态_开放购买
	const STATUS_SELECTING = 2; //状态_开奖中
	const STATUS_FINISH = 3; //状态_已开奖
	const STATUS_FAILED = 4; //状态_已失败

	static $product_cache = array();

	/**
	 * 获取有效商品
	 */
	function getProduct($limit = 2){

		$ret = $this->db('yungou')->findAll(array('status'=>array(\DB\Yungou::STATUS_OPENING, \DB\Yungou::STATUS_SELECTING)), '', '', $limit);

		return clearTableName($ret);
	}

	/**
	 * 获得云购商品下注总量
	 * @param  [type] $yungou_id [description]
	 * @return [type]            [description]
	 */
	function getChipSum($yungou_id){

		return $this->db('order_yungou')->getChipSum($yungou_id);
	}

	/**
	 * 获取云购商品详情
	 * @param  int $product_id   商品ID
	 * @return string $field   商品字段
	 */
	function detail($yungou_id, $field=''){

		if(!$yungou_id)return;

		if(isset(self::$product_cache[$yungou_id])){
			$ret = self::$product_cache[$yungou_id];
		}else{
			$ret = $this->db('yungou')->find(array('id'=>$yungou_id));
			$ret = clearTableName($ret);
			if(!$ret)return;
			self::$product_cache[$yungou_id] = $ret;
		}
		
		if($field && $ret){
			return $ret[$field];
		}else{
			return $ret;
		}
	}

	/**
	 * 更新云购商品状态
	 * @param  int $yungou_id 商品ID
	 * @param  int $status    新状态
	 * @return bool
	 */
	function updateStatus($yungou_id, $status){

		if(!$yungou_id || !$status){
			return ;
		}

		return $this->db('yungou')->update($yungou_id, array('status'=>$status));
	}
}
?>