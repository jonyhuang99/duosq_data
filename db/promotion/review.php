<?php
//促销信息审核
namespace DB;

class Review extends _Db {

	var $name = 'Review';
	var $useDbConfig = 'promotion';

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
}
?>