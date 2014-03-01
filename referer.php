<?php
//DAL:来源信息访问模块
namespace DAL;

class Referer extends _Dal {

	//判断是否劣质来源
	function isPoor(){

		if($this->isMarkValid())return false;

		if($_COOKIE['referer'] && strpos($_COOKIE['referer'], 'duosq.com')===false){
			$referer = $_COOKIE['referer'];
		}else{
			$referer = '';
		}

		//没有来源即为廉价流量
		if(!$referer)return false;

		return $this->isHit($referer, 'referer_poor');
	}

	//判断是否优质来源
	function isGood(){

		if($this->isMarkValid())return true;

		if($_COOKIE['referer'] && strpos($_COOKIE['referer'], 'duosq.com')===false){
			$referer = $_COOKIE['referer'];
		}else{
			$referer = '';
		}

		//没有来源即为廉价流量
		if(!$referer)return false;

		return $this->isHit($referer, 'referer_good');
	}

	//对比来源是否命中指定来源池
	function isHit($referer, $conf='referer_poor'){

		$referers = C('comm', $conf);
		foreach ($referers as $u) {
			if (stripos(urldecode($referer), $u) !== false){
				return true;
			}
		}

		return false;
	}

	//有效的推广来源
	function isMarkValid(){

		$mark_detail = D('mark')->detail();
		if($mark_detail){
			$time_diff = time() - strtotime($mark_detail['createtime']);
			if($time_diff < 10)
				return true;
		}
		return false;
	}
}
?>