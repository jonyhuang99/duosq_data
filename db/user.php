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
		$this->create();
		I('ip2location');
		$ip2location = new \ip2location();
		$ip = getIp();
		$area = $ip2location->province($ip);
		$area_detail = $ip2location->location($ip);

		return parent::save(array('alipay'=>$alipay, 'mark_id'=>$mark_id, 'sc_risk'=>$sc_risk, 'reg_ip'=>$ip, 'reg_area'=>$area, 'reg_area_detail'=>$area_detail));
	}

	//更新用户数据
	function update($user_id, $data=array()){

		if(!$user_id)return;
		$data['id'] = $user_id;
		$ret = parent::save(arrayClean($data));
		return $ret;
	}

	//置空save，只允许从add/update进入
	function save(){}
}

?>