<?php
namespace DB;

class User extends _Db {

	var $name = 'User';

	function getIdByAlipay($alipay){

		if(!$alipay)return;
		return $this->field('id', array('alipay'=>$alipay));
	}

	function getInfo($user_id){

		if(!$user_id)return;
		$field = 'id,source_id,alipay';
		$user = $this->find(array('id'=>$user_id), $field);
		return clearTableName($user);
	}

	function add($alipay){

		if(!$alipay)return;
		$this->create();
		return $this->save(array('alipay'=>$alipay));
	}
}

?>