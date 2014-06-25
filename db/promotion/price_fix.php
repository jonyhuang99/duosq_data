<?php
//促销信息审核
namespace DB;

class PriceFix extends _Db {

	var $name = 'PriceFix';
	var $useDbConfig = 'promotion';

	//置空默认save方法
	function save(){}

	function addOrUpdate($sp, $goods_id, $price_now, $expire){

		if(!$sp || !$goods_id || !$price_now || !$expire)return;
		$id = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id));
		if(!$id){
			$this->create();
			return parent::save(array('sp'=>$sp, 'goods_id'=>$goods_id, 'price_now'=>$price_now, 'expire'=>$expire));
		}else{
			return parent::save(array('id'=>$id, 'sp'=>$sp, 'goods_id'=>$goods_id, 'price_now'=>$price_now, 'expire'=>$expire));
		}
	}
}
?>