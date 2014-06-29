<?php
//审核品牌关联
namespace DB;

class ReviewBrand extends _Db {

	var $name = 'ReviewBrand';
	var $useDbConfig = 'promotion';

	//特卖待审状态定义
	const STATUS_WAIT_REVIEW = 0;
	const STATUS_NORMAL = 1;
	const STATUS_INVALID = 2;

	//置空默认save方法
	function save(){}

	function add($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$id = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id));
		if(!$id){
			$this->create();
			return parent::save(array('sp'=>$sp, 'goods_id'=>$goods_id));
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