<?php
//DAL:用户数据访问模块
namespace DAL;

class user extends _Dal {

	//获取用户信息
	function detail($userid){
		if(!$userid)return;
		$ret = $this->db('user')->find(array('id'=>$userid));
		return clearTableName($ret);
	}
}

?>