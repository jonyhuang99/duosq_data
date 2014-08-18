<?php
//促销信息审核
namespace DB;

class PriceFix extends _Db {

	var $name = 'PriceFix';
	var $useDbConfig = 'promotion';

	function addOrUpdate($sp, $goods_id, $price_now, $expire){

		if(!$sp || !$goods_id || !$price_now || !$expire)return;
		$id = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id));
		if(!$id){

			return parent::add(array('sp'=>$sp, 'goods_id'=>$goods_id, 'price_now'=>$price_now, 'expire'=>$expire));
		}else{
			return parent::update($id, array('sp'=>$sp, 'goods_id'=>$goods_id, 'price_now'=>$price_now, 'expire'=>$expire));
		}
	}
}
?>