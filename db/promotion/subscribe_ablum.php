<?php
//专辑数据
namespace DB;

class SubscribeAblum extends _Db {

	var $name = 'SubscribeAblum';
	var $useDbConfig = 'promotion';

	var $validate = array(
							'midcat'=>VALID_NOT_EMPTY,
							'title'=>VALID_NOT_EMPTY,
							'price'=>VALID_NOT_EMPTY,
							'cover_1'=>VALID_NOT_EMPTY,
							'cover_2'=>VALID_NOT_EMPTY,
						);

	//新增专辑，返回专辑ID
	function add($data){

		if($this->find(array('url'=>$data['url']))){
			$this->validationErrors['exist'] = 1;
			return;
		}

		$pass = $this->validForm($data);
		if(!$pass)return;

		return parent::add($data);
	}

	//更新专辑
	function update($id, $data){

		$pass = $this->validForm($data);
		if(!$pass)return;

		return parent::update($id, $data);
	}

	//验证表单参数
	function validForm($data){

		$pass = true;

		$url_pass = false;
		foreach(C('options', 'ablum_sp') as $sp=>$name){
			if(stripos($data['url'], $sp.'.')!==false){
				$url_pass = true;
			}
		}

		if(!$url_pass){
			$this->validationErrors['url'] = 1;
			$pass = false;
		}

		if(stripos($data['url'], 'tmall') && (!$data['url_sclick'] || !stripos($data['url_sclick'], 'click'))){
			$this->validationErrors['url_sclick'] = 1;
			$pass = false;
		}

		if(!preg_match('/^[0-9]+\-[0-9]+$/i', $data['price'])){
			$this->validationErrors['price'] = 1;
			$pass = false;
		}

		if($data['expire_start'] && $data['expire_start']!='0000-00-00 00:00:00' && strtotime($data['expire_start']) < time()-MONTH){
			$this->validationErrors['expire'] = 1;
			$pass = false;
		}

		if($data['expire_end'] && $data['expire_end']!='0000-00-00 00:00:00' && strtotime($data['expire_end']) < time()-MONTH){
			$this->validationErrors['expire'] = 1;
			$pass = false;
		}

		return $pass;
	}
}
?>