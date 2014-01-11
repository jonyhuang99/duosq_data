<?php
//DAL:用户数据访问模块
namespace DAL;

class user extends _Dal {

	//获取用户信息
	function detail($user_id, $field = false){
		if(!$user_id)return;
		$ret = $this->db('user')->find(array('id'=>$user_id));
		clearTableName($ret);
		if($field){
			return $ret[$field];
		}else{
			return $ret;
		}
	}

	//根据淘宝订单末位，拉出匹配的用户
	function getUserByTaobaoNo($no){
		if(!$no)return;
		$user_id = $this->db('user_taobao')->field('user_id', array('taobao_no'=>$no));
		return $this->detail($user_id);
	}
}

?>