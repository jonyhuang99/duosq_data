<?php
//专辑数据
namespace DB;

class SubscribeTaskEdm extends _Db {

	var $name = 'SubscribeTaskEdm';
	var $useDbConfig = 'promotion';

	//专辑状态定义
	const STATUS_NORMAL = 1; //正常
	const STATUS_INVALID = 0; //无效
	const TARGET_SUBSCRIBE_NORMAL = 1; //针对subscribe按期接受的订户
	const TARGET_SUBSCRIBE_GLOBAL = 2; //针对subscribe所有订户
	const TARGET_USER_GLOBAL = 3; //针对非订阅所有订户

	var $validate = array(
							'subject'=>VALID_NOT_EMPTY,
							'content'=>VALID_NOT_EMPTY,
						);

	//新增专辑，返回专辑ID
	function add($data){

		$pass = $this->validForm($data);
		if(!$pass)return;

		$this->fixSearch($data);
		return parent::add($data);
	}

	//更新专辑
	function update($id, $data){

		$pass = $this->validForm($data);
		if(!$pass)return;

		$this->fixSearch($data);
		return parent::update($id, $data);
	}

	//验证表单参数
	function validForm($data){

		if(@$data['expire'] && !preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/i', $data['expire'])){
			$this->validationErrors['expire'] = 1;
			return false;
		}
		return true;
	}

	//为支持数组字段增加逗号结尾
	function fixSearch(&$data){
		foreach($data as $field => &$value){
			if(in_array($field, array('setting_albumcat','setting_midcat','setting_brand','setting_clothes_style_girl','setting_clothes_style_boy','setting_clothes_size_girl','setting_clothes_size_boy','setting_shoes_size_girl','setting_shoes_size_boy')) && $value){
				$value = $value . ',';
			}
		}
	}
}
?>