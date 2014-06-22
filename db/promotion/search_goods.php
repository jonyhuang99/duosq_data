<?php
//搜索索引：非特卖商品
namespace DB;

class SearchGoods extends _Db {

	var $name = 'SearchGoods';
	var $useDbConfig = 'promotion';

	//置空默认save方法
	function save(){}

	function add($sp, $goods_id, $name, $weight=0){

		if(!$sp || !$goods_id || !$name)return;
		$id = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id));
		if($id){
			return $id;
		}else{
			$this->create();
			return parent::save(array('sp'=>$sp, 'goods_id'=>$goods_id, 'name'=>$name, 'weight'=>$weight));
		}
	}

	function delete($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$this->query("DELETE FROM duosq_promotion.search_goods WHERE sp = '{$sp}' AND goods_id = '{$goods_id}'");
	}
}
?>