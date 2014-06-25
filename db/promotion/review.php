<?php
//促销信息审核
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

	//置空默认save方法
	function save(){}

	function add($sp, $goods_id, $type){

		if(!$sp || !$goods_id || !$type)return;
		$id = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id));
		if(!$id){
			$this->create();
			return parent::save(array('sp'=>$sp, 'goods_id'=>$goods_id, 'type'=>$type));
		}
	}

	//更改纠错信息状态
	function updateStatus($sp, $goods_id, $status){

		if(!$sp || !$goods_id)return;
		$id = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id));
		if($id){
			return parent::save(array('id'=>$id, 'status'=>$status));
		}
	}
}
?>