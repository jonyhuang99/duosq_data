<?php
//专辑数据
namespace DB;

class SubscribeAlbum extends _Db {

	var $name = 'SubscribeAlbum';
	var $useDbConfig = 'promotion';

	//专辑状态定义
	const STATUS_NORMAL = 1; //正常
	const STATUS_INVALID = 0; //无效
	const STATUS_WAIT_REVIEW = 2; //待审

	var $validate = array(
							//'setting_albumcat'=>VALID_NOT_EMPTY,
							'url'=>VALID_NOT_EMPTY,
							'title'=>VALID_NOT_EMPTY,
							//'setting_brand'=>VALID_NOT_EMPTY,
							'price'=>VALID_NOT_EMPTY,
							'cover_1'=>VALID_NOT_EMPTY,
						);

	//新增专辑，返回专辑ID
	function add($data){

		if($this->find(array('url'=>$data['url']))){
			$this->validationErrors['exist'] = 1;
			return;
		}

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

		$pass = true;

		//非表单提交不做校验
		if(!isset($data['url'])){
			$url_pass = true;
		}else{

			if(stripos($data['url'], 'tmall') && (!$data['url_sclick'] || !stripos($data['url_sclick'], 'click'))){
				//$this->validationErrors['url_sclick'] = 1;
				//$pass = false;
			}
		}

		if(isset($data['price']) && !preg_match('/^[0-9]+\-[0-9]+$/i', $data['price'])){
			$this->validationErrors['price'] = 1;
			$pass = false;
		}

		if(isset($data['expire_start']) && $data['expire_start'] && $data['expire_start']!='0000-00-00 00:00:00' && strtotime($data['expire_start']) < time()-MONTH){
			$this->validationErrors['expire'] = 1;
			$pass = false;
		}

		if(isset($data['expire_end']) && $data['expire_end'] && ($data['expire_end']=='0000-00-00 00:00:00' || !$data['expire_end'])){
			$this->validationErrors['expire'] = 1;
			$pass = false;
		}

		return $pass;
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