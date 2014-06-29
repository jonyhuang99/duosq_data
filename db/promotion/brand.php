<?php
//品牌数据
namespace DB;

class Brand extends _Db {

	var $name = 'brand';
	var $useDbConfig = 'promotion';

	//置空默认save方法
	function save(){}

	function addOrUpdate($cat, $name, $weight){

		if(!$cat || !$name)return;
		$name_en = '';
		$name = goodsName($name);
		if(strpos($name, '/')){
			list($n1, $n2) = explode('/', $name);
			if(preg_match('/^[a-z0-9\\x20\'\’\-\.\&\%\‘\:\·\~]+$/i', $n1)){
				$name_en = $n1;
				$name = $n2;
			}else{
				$name_en = $n2;
				$name = $n1;
			}
		}

		$exist = $this->find(array('cat'=>$cat, 'name'=>$name));
		if(!$exist && $name_en) $this->find(array('cat'=>$cat, 'name_en'=>$name_en));
		clearTableName($exist);
		if(!$exist){
			$this->create();
			return parent::save(array('cat'=>$cat, 'name'=>$name, 'name_en'=>$name_en, 'weight'=>$weight));
		}else{
			return parent::save(array('id'=>$exist['id'], 'weight'=>$weight + $exist['weight']));
		}
	}
}
?>