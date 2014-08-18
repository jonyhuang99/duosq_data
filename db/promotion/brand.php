<?php
//品牌数据
namespace DB;

class Brand extends _Db {

	var $name = 'brand';
	var $useDbConfig = 'promotion';

	//新增品牌，返回品牌ID
	function add($data){

		if(!$data['name'] && !$data['name_en'])return;
		return parent::add($data);
	}
}
?>