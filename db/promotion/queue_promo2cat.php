<?php
//特卖队列分类操作基类
namespace DB;

class QueuePromo2cat extends _Db {

	var $name = 'QueuePromo2cat';
	var $useDbConfig = 'promotion';

	//置空save，只允许从add/update进入
	function save(){}

	//新增促销信息分类数据
	function add($data){
		if(!$data['sp'] || !$data['goods_id'])return;

		$this->create();
		return parent::save($data);
	}

	//更新促销信息分类数据
	function update($sp, $goods_id, $data){

		if(!$sp || !$goods_id || !$data)return;

		$hits = $this->findAll(array('sp'=>$sp, 'goods_id'=>$goods_id));
		$hit_ids = array();
		clearTableName($hits);
		if(!$hits)return;
		foreach($hits as $hit){
			$data['id'] = $hit['id'];
			parent::save($data);
		}
		return true;
	}
}
?>