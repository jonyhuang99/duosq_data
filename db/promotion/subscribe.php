<?php
//订阅数据库操作基类
namespace DB;

class Subscribe extends _Db {

	var $name = 'Subscribe';
	var $useDbConfig = 'promotion';

	//订阅状态定义
	const STATUS_NORMAL = 1; //正常
	const STATUS_STOP = 0; //停止订阅

	//返回指定账户订阅信息
	function detail($account, $channel='email', $field=''){

		if(!$account || !$channel)return false;
		$ret = parent::findAll(array('account'=>$account, 'channel'=>$channel));
		if(empty($ret[0]))return false;
		$ret = clearTableName($ret[0]);
		if($field){
			return $ret[$field];
		}else{
			return $ret;
		}
	}

	//增加订阅账户
	function add($account, $channel='email', $data=array()){

		if(!$account || !$channel)return false;

		$data['account'] = $account;
		$data['channel'] = $channel;

		I('ip2location');
		$ip2location = new \ip2location();
		$ip = getIp();
		$area = $ip2location->province($ip);
		$area_detail = $ip2location->location($ip);

		if(@$_COOKIE['referer'] && strpos($_COOKIE['referer'], 'duosq.com')===false){
			$referer = $_COOKIE['referer'];
		}else{
			$referer = '';
		}

		$mark = D('mark')->detail();
		if($mark){
			$data['mark_id'] = $mark['id'];
			$data['mark_sc'] = $mark['sc'];
		}
		$data['reg_ip'] = $ip;
		$data['reg_area'] = $area;
		$data['reg_area_detail'] = $area_detail;
		$data['reg_client'] = getBrowser();
		$data['reg_referer'] = $referer;

		return parent::add(arrayClean($data));
	}

	//修改订阅账户配置
	function update($account, $channel='email', $data){

		if(!$account || !$channel)return false;
		//允许置空某项配置
		if(!$data)return false;

		$id = $this->detail($account, $channel, 'id');
		if(!$id)return false;

		$ret = parent::update($id, $data);
		return $ret;
	}
}
?>