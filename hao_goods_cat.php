<?php
//DAL:商品分类导航模块
namespace DAL;

class HaoGoodsCat extends _Dal {

	static $cat_detail = array();

	//获取商品分类中文名
	function getName($cat){
		$map = C('hao_goods_cat');
		return $map[$cat];
	}

	//获取商品分类导航子分类信息
	function getDetail($cat=null){

		if($cat)
			$cat_name = $this->getName($cat);

		if($cat && isset(self::$cat_detail[$cat_name])){
			$cat_detail = self::$cat_detail[$cat_name];
		}else{
			$cat_detail = $this->parseDetail();
			self::$cat_detail = $cat_detail;
		}

		if($cat){
			return $cat_detail[$cat_name];
		}else{
			return $cat_detail;
		}
	}

	//解析分类配置文件，返回格式
	//array(cat => array(subcat => array('img'=>'二级分类url', 'nodes'=>array(sp=>array('intro'=>'xx','url'=>'xxx')))));
	private function parseDetail(){

		$file = file(MYCONFIGS . 'hao_goods_cat_detail');
		if(!$file)return false;

		$ret = array();
		foreach($file as $line){

			$count = substr_count($line, "\t");
			$line = trim($line);
			if($count == 0){
				$ret[$line] = '';
				$last_cat = $line;
			}

			if($count == 1){
				$last_subcat = $line;
			}

			if($count == 2){
				@list($sp, $url) = explode('|', $line);
				$ret[$last_cat][$last_subcat]['nodes'][$sp] = array('url'=>$url);
			}
		}

		return $ret;
	}
}
?>