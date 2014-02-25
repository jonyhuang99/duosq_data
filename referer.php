<?php
//DAL:来源信息访问模块
namespace DAL;

class Referer extends _Dal {

	//判断是否劣质来源
	function isPoor(){

		if($_COOKIE['referer'] && strpos($_COOKIE['referer'], 'duosq.com')===false){
			$referer = $_COOKIE['referer'];
		}else{
			$referer = '';
		}

		//没有来源即为廉价流量
		if(!$referer)return false;

		$referers = C('comm', 'referer_poor');
		foreach ($referers as $u) {
			if (stripos(urldecode($referer), $u) !== false){
				return true;
			}
		}
	}

	//判断是否优质来源
	function isGood(){

		if($_COOKIE['referer'] && strpos($_COOKIE['referer'], 'duosq.com')===false){
			$referer = $_COOKIE['referer'];
		}else{
			$referer = '';
		}

		//没有来源即为廉价流量
		if(!$referer)return false;

		$referers = C('comm', 'referer_good');
		foreach ($referers as $u) {
			if (stripos(urldecode($referer), $u) !== false){
				return true;
			}
		}
	}
}
?>