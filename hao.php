<?php
//DAL:商城导航模块
namespace DAL;

class Hao extends _Dal {

	static $cat_detail = array();
	static $cat_url_m = array();
	static $cat_file = array();

	//获取商城的子分类信息
	function getSubcat($sp, $cat=null){

		if(!$sp)return;
		if(isset(self::$cat_detail[$sp])){
			$cat_detail = self::$cat_detail[$sp];
		}else{
			$cat_detail = $this->parseConfig($sp);
			self::$cat_detail[$sp] = $cat_detail;
		}

		if($cat){
			return $cat_detail[$cat];
		}else{
			return $cat_detail;
		}
	}

	//获取商城的节点分类信息
	function getNodecat($sp, $cat, $subcat){

		if(!$sp || !$cat || !$subcat)return;
		if(isset(self::$cat_detail[$sp])){
			$cat_detail = self::$cat_detail[$sp];
		}else{
			$cat_detail = $this->parseConfig($sp);
			self::$cat_detail[$sp] = $cat_detail;
		}

		if(count($cat_detail[$cat][$subcat]['nodes'])>10){
			return array_slice($cat_detail[$cat][$subcat]['nodes'], 0, 10);
		}else{
			return $cat_detail[$cat][$subcat]['nodes'];
		}
	}

	//解析分类配置文件，返回格式
	//array(cat => array(subcat => array('url'=>'二级分类url', 'nodes'=>array(nodecat=>url))));
	private function parseConfig($sp){

		$file = file(MYCONFIGS . 'hao'. DS . $sp);
		if(!$file)return false;

		$ret = array();
		array_shift($file);
		foreach($file as $line){

			$count = substr_count($line, "\t");
			$line = trim($line);
			if($count == 0){
				$ret[$line] = '';
				$last_cat = $line;
			}

			if($count == 1){
				list($subcat, $url) = explode('：', $line);
				$ret[$last_cat][$subcat] = array('url'=>$this->parseUrl($sp, $url));
				$last_subcat = $subcat;
			}

			if($count == 2){
				list($nodecat, $url) = explode('：', $line);
				$ret[$last_cat][$last_subcat]['nodes'][$nodecat] = $this->parseUrl($sp, $url);
				$last_subcat = $subcat;
			}
		}

		return $ret;
	}

	//根据分类配置首行的url模板，还原url
	private function parseUrl($sp, $orign_url){

		if(isset(self::$cat_url_m[$sp])){
			$url_m = self::$cat_url_m[$sp];
		}else{
			$file = file(MYCONFIGS . 'hao'. DS . $sp);
			if(!$file)return false;
			$url_tpl = array_shift($file);
			$url_tpl_group = explode(',', $url_tpl);
			$url_m = array();
			foreach($url_tpl_group as $group){
				list($si, $url) = explode('|', $group);
				$url_m[$si] = trim($url);
			}
			self::$cat_url_m[$sp] = $url_m;
		}

		foreach($url_m as $si=>$url){
			$orign_url = r($si, $url, $orign_url);
		}

		return $orign_url;
	}

	//用户自定义商城导航
	function set($user_id, $sp){
		if(!$user_id)return;

		if($sp){
			$this->db('hao')->save(array('user_id'=>$user_id, 'design'=>serialize($sp)));
		}else{
			$this->db('hao')->save(array('user_id'=>$user_id, 'design'=>$sp));
		}
	}

	//读取用户指定商城导航
	function get($user_id){

		$design = $this->db('hao')->find(array('user_id'=>$user_id));
		clearTableName($design);
		if($design){
			if($design['design'])
				return unserialize($design['design']);
			else
				return 'empty';
		}
	}

	//用户每日签到奖励
	function doSign($user_id){

		//判断是否有领奖权限
		if(!D('myuser')->canGetCashgift()){
			return -1;
		}
		$ret = $this->redis('lock')->getlock(\Redis\Lock::LOCK_SIGN, $user_id);
		if(!$ret)return 0;

		$user_id = D('myuser')->getId();

		$last_day = D('order')->searchSubOrders('sign', array('createdate'=>date('Y-m-d', time()-DAY), 'user_id'=>$user_id), 1);
		if($last_day){
			$days += $last_day['days'];
		}else{
			$days = 0;
		}
		$ret = D('order')->addSign($user_id, array('amount'=>C('comm', 'sign_jfb'), 'days'=>$days));

		if($ret)
			return C('comm', 'sign_jfb');
		else{
			$this->redis('lock')->unlock(\Redis\Lock::LOCK_SIGN, $user_id);
			return -2;
		}

	}

	//判断用户今日是否已经签到
	function hasSign($user_id){

		return $this->redis('lock')->check(\Redis\Lock::LOCK_SIGN, $user_id);
	}
}
?>