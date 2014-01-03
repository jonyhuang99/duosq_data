<?php
//DAL:商城数据访问模块
namespace DAL;

class Shop extends _Dal {

	function detail($sp) {

		if(!$sp)return;
		$shop = $this->db('shop')->find(array('sp'=>$sp));
		return clearTableName($shop);
	}

	function getName($sp){

		if(!$sp)return;
		$shop = $this->detail($sp);
		return $shop['name'];
	}
}
?>