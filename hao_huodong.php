<?php
//DAL:商品分类导航模块
namespace DAL;

class HaoHuodong extends _Dal {

	static $hd_detail = array();

	//获取商城活动详情
	function getDetail($sp, $cat_name=null){

		if(!$sp)return;

		if(!self::$hd_detail){
			$hd_detail = $this->parseDetail();
			self::$hd_detail = $hd_detail;
		}

		if($sp && $cat_name){
			return @self::$hd_detail[$sp][$cat_name];
		}

		return self::$hd_detail[$sp]['_site_'];
	}

	//解析分类配置文件，返回格式
	//array(cat => array(subcat => array('img'=>'二级分类url', 'nodes'=>array(sp=>array('intro'=>'xx','url'=>'xxx')))));
	private function parseDetail(){

		$file = file(MYCONFIGS . 'hao_huodong');
		if(!$file)return false;

		$ret = array();
		foreach($file as $line){

			$count = substr_count($line, "\t");
			$line = trim($line);
			if($count == 0){
				$ret[$line] = '';
				$last_sp = $line;
			}

			if($count == 1){
				$info = explode('|', $line);
				if(count($info) == 2){
					if($info[1])$ret[$last_sp]['_site_'][] = array('huodong'=>$info[0], 'url'=>$info[1]);
				}

				if(count($info) == 3){
					if($info[2])$ret[$last_sp][$info[0]][] = array('huodong'=>$info[1], 'url'=>$info[2]);
				}
			}
		}

		return $ret;
	}
}
?>