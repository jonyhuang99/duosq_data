<?php
//用户表数据操作
namespace DB;

class User extends _Db {

	var $name = 'User';

	//根据用户ID获取支付宝
	function getIdByAlipay($alipay){

		if(!$alipay)return;
		return $this->field('id', array('alipay'=>$alipay));
	}

	//新增用户
	function add($alipay, $mark_id=0, $sc_risk=0){

		if(!$alipay)return;

		I('ip2location');
		$ip2location = new \ip2location();
		$ip = getIp();
		$area = $ip2location->province($ip);
		$area_detail = $ip2location->location($ip);
		$client = getAgent();
		$utmo = D('track')->get();

		if(@$_COOKIE['referer'] && strpos($_COOKIE['referer'], 'duosq.com')===false){
			$referer = $_COOKIE['referer'];
		}else{
			$referer = '';
		}

		//赚钱来源，默认轻度黑名单
		$status = 1;
		if($referer){
			D('user');
			if(D('referer')->isPoor()){
				$status = \DAL\User::STATUS_BLACK_1;
			}
		}

		$mark = D('mark')->detail();
		if($mark){
			$mark_id = $mark['id'];
			$mark_sc = $mark['sc'];
		}else{
			$mark_id = 0;
			$mark_sc = '';
		}

		$sc_risk = D('mark')->getScRisk();

		return parent::add(array('status'=>$status, 'alipay'=>$alipay, 'mark_id'=>$mark_id, 'mark_sc'=>$mark_sc, 'sc_risk'=>$sc_risk, 'reg_ip'=>$ip, 'reg_area'=>$area, 'reg_area_detail'=>$area_detail, 'reg_client'=>$client, 'reg_referer'=>$referer, 'utmo'=>$utmo));
	}

	//更新用户数据
	function update($user_id, $data=array()){

		if(!$user_id)return;
		$ret = parent::update($user_id, arrayClean($data));
		return $ret;
	}
}

?>