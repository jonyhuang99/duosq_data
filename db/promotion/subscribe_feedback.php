<?php
//反馈数据
namespace DB;

class SubscribeFeedback extends _Db {

	var $name = 'SubscribeFeedback';
	var $useDbConfig = 'promotion';

	var $validate = array('content'=>VALID_NOT_EMPTY);

	//新增品牌，返回品牌ID
	function add($data){

		$pass = true;
		if(!@$data['content'] || strlen($data['content']) < 10){
			$this->validationErrors['content'] = 1;
			$pass = false;
		}

		if($data['qq'] && !preg_match(VALID_NUMBER, $data['qq'])){
			$this->validationErrors['qq'] = 1;
			$pass = false;
		}

		if(!$pass)return false;

		return parent::add($data);
	}
}
?>