<?php
//特卖队列操作基类
namespace DB;

class QueuePromo extends _Db {

	var $name = 'QueuePromo';
	var $useDbConfig = 'promotion';

	//特卖状态定义
	const STATUS_WAIT_REVIEW = 0;
	const STATUS_NORMAL = 1;
	const STATUS_INVALID = 2;

	//特卖类型定义
	const TYPE_DISCOUNT = 1;
	const TYPE_HOT = 2;
	const TYPE_HUODONG = 3;


	//新增促销信息分类数据
	function add($data){
		if(!$data['sp'] || !$data['goods_id'])return;
		return parent::add($data);
	}

	//更新促销信息分类数据
	function update($sp, $goods_id, $data){

		if(!$sp || !$goods_id || !$data)return;

		$id = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id));
		if(!$id)return;

		return parent::update($id, $data);
	}
}
?>