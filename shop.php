<?php
//DAL:商城数据访问模块
namespace DAL;

class Shop extends _Dal {

	static $detail = array();

	function detail($sp, $field=false) {

		if(!$sp)return;

		if(isset(self::$detail[$sp])){
			$shop = self::$detail[$sp];
		}else{
			$key = 'shop:detail:sp:'.$sp;
			$cache = D('cache')->get($key);
			if($cache){
				$shop = D('cache')->ret($cache);
			}else{
				$shop = $this->db('shop')->find(array('sp'=>$sp));
				clearTableName($shop);

				D('cache')->set($key, $shop, MINUTE*10);
			}

			self::$detail[$sp] = $shop;
		}

		if($field){
			return $shop[$field];
		}else{
			return $shop;
		}
	}

	function getName($sp, $long=false){

		if(!$sp)return;
		$shop = $this->detail($sp);
		if($long){
			if(mb_strlen($sp) < 3){
				return $shop['name'].'商城';
			}
		}
		return $shop['name'];
	}
}
?>