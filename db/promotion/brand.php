<?php
//品牌数据
namespace DB;

class Brand extends _Db {

	var $name = 'brand';
	var $useDbConfig = 'promotion';

	//置空save，只允许从add/update进入
	function save(){}

	//新增品牌数据，返回品牌ID
	function add($data){
		if(!$data['name'] && !$data['name_en'])return;

		$this->create();
		return parent::save($data);
	}

	//更新品牌数据
	function update($brand_id, $data){

		if(!$brand_id || !$data)return;

		$data['id'] = $brand_id;
		if(!$this->find(array('id'=>$brand_id)))return;
		return parent::save($data);
	}
}
?>