<?php
//DAL:用户数据访问模块
namespace DAL;

class user extends _Dal {

	const ALIPAY_VALID_NONE = 0;
	const ALIPAY_VALID_JFB = 1;
	const ALIPAY_VALID_TRUENAME = 2;
	const ALIPAY_VALID_ERROR = 10;

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

	//获取用户昵称
	function getNickname($user_id){

		if($this->sys($user_id))return '多省钱官方';
		$nickname = $this->detail($user_id, 'nickname');
		if($nickname){
			return $nickname;
		}else{
			return mask($this->detail($user_id, 'alipay'));
		}
	}

	//用户支付宝验证信息更新
	function validAlipay($user_id, $level=0){

		if(!$user_id)return;
		$curr = D('user')->detail($user_id, 'alipay_valid');
		if($curr==self::ALIPAY_VALID_TRUENAME && $level<$curr){
			return;//不允许将实名认证级别的支付宝降为低级别
		}
		return $this->db('user')->update($user_id, array('alipay_valid'=>$level));
	}

	//根据淘宝订单末位，拉出匹配的用户
	function getUserByTaobaoNo($no){

		if(!$no)return;
		$user_id = $this->db('user_taobao')->field('user_id', array('taobao_no'=>$no));
		return $this->detail($user_id);
	}

	//标识用户下过单
	function markUserHasOrder($user_id){
		return $this->db('user')->update($user_id, array('has_order'=>1));
	}

	//判断用户是否系统内部用户
	function sys($user_id){
		if(!$user_id)return false;
		if($user_id < 100)return true;
		return false;
	}

	//判断7天内C段IP是否注册过
	function checkIpC($ip_c){
		$day = date('Y-m-d', time() - 7*86400);
		$alipay = D('myuser')->getAlipay();
		if($alipay){//跳过自己，防止任务集分宝误判
			$hit = $this->db('user')->query("SELECT * FROM user WHERE reg_ip like '{$ip_c}.%' AND createdate>'{$day}' AND alipay <> '{$alipay}'");
		}else{
			$hit = $this->db('user')->query("SELECT * FROM user WHERE reg_ip like '{$ip_c}.%' AND createdate>'{$day}'");
		}
		if($hit)return true;
	}
}

?>