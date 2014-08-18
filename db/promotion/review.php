<?php
//审核促销信息
namespace DB;

class Review extends _Db {

	var $name = 'Review';
	var $useDbConfig = 'promotion';

	//特卖待审状态定义
	const STATUS_WAIT_REVIEW = 0;
	const STATUS_NORMAL = 1;
	const STATUS_INVALID = 2;

	//特卖待审类型定义
	const TYPE_ERROR = 1;
	const TYPE_SALE_OUT = 2;
	const TYPE_PRICE_UP = 3;
	const TYPE_PRICE_DOWN = 5;
	const TYPE_OFF_SALE = 4;

	function add($sp, $goods_id, $type){

		if(!$sp || !$goods_id || !$type)return;
		$id = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id, 'createdate'=>date('Y-m-d')));
		if(!$id){
			return parent::add(array('sp'=>$sp, 'goods_id'=>$goods_id, 'type'=>$type));
		}
	}

	//更改纠错信息状态
	function updateStatus($sp, $goods_id, $status){

		if(!$sp || !$goods_id)return;
		$ids = $this->findAll(array('sp'=>$sp, 'goods_id'=>$goods_id));
		$ids = clearTableName($ids);
		if($ids){
			foreach($ids as $id){
				$ret = parent::update($id['id'], array('status'=>$status));
			}
			return $ret;
		}
	}
}
?>