<?php
//特卖队列分类操作基类
namespace DB;

class QueuePromo2cat extends _Db {

	var $name = 'QueuePromo2cat';
	var $useDbConfig = 'promotion';

	//新增促销信息分类数据
	function add($data){

		if(!$data['sp'] || !$data['goods_id'])return;
		return parent::add($data);
	}

	//更新促销信息分类数据
	function update($sp, $goods_id, $data){

		if(!$sp || !$goods_id || !$data)return;

		$hits = $this->findAll(array('sp'=>$sp, 'goods_id'=>$goods_id));
		$hit_ids = array();
		$hits = clearTableName($hits);
		if(!$hits)return;
		foreach($hits as $hit){
		 	$ret = parent::update($hit['id'], $data);
		}
		return $ret;
	}

	//删除促销排序
	function delete($sp, $goods_id, $subcat=''){

		if(!$sp || !$goods_id)return;

		if($subcat){
			$this->query("DELETE FROM duosq_promotion.queue_promo2cat WHERE sp = '{$sp}' AND goods_id = '{$goods_id}' AND subcat = '{$subcat}'");
		}else{
			$this->query("DELETE FROM duosq_promotion.queue_promo2cat WHERE sp = '{$sp}' AND goods_id = '{$goods_id}'");
		}
	}
}
?>