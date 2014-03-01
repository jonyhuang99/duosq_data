<?php
//DAL:用户数据访问模块
namespace DAL;

class user extends _Dal {

	const ALIPAY_VALID_NONE = 0;
	const ALIPAY_VALID_JFB = 1;
	const ALIPAY_VALID_TRUENAME = 2;
	const ALIPAY_VALID_ERROR = 10;

	const STATUS_BLACK_1 = 11; //黑名单-收入打折
	const STATUS_BLACK_2 = 12; //黑名单-收入为0

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

	//搜索用户
	function search($condition){

		$ret = $this->db('user')->findAll($condition);
		return clearTableName($ret);
	}

	//支付宝到用户ID的转换
	function Alipay2userid($alipay){
		if($alipay)
			return $this->db('user')->field('id', array('alipay'=>$alipay));
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

	//返回用户状态
	function getStatus($user_id){

		if(!$user_id)return false;
		return $this->detail($user_id, 'status');
	}

	//是否黑名单
	function isBlack($user_id){

		if(!$user_id)return false;
		$status = $this->getStatus($user_id);
		if($status == self::STATUS_BLACK_1 || $status == self::STATUS_BLACK_2){
			return true;
		}else{
			return false;
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

	//标识用户下过单
	function markUserCashgiftInvalid($user_id){
		return $this->db('user')->update($user_id, array('can_get_cashgift'=>0));
	}

	//标识用户为黑名单
	function markBlack($user_id, $status=11){
		if(!$status)return;
		if(is_array($user_id)){
			foreach($user_id as $id){
				//系统名单用户除外
				if(!$this->sys($id)){
					if(!$this->db('user')->find(array('id'=>$id, 'status'=>"> $status"))){
						$ret = $this->db('user')->update($id, array('status'=>$status));
					}
				}
			}
		}else{
			if(!$this->sys($user_id)){
				if(!$this->db('user')->find(array('id'=>$user_id, 'status'=>"> $status"))){
					$ret = $this->db('user')->update($user_id, array('status'=>$status));
				}
			}
		}
		return true;
	}

	//判断用户是否系统内部用户
	function sys($user_id){
		if(!$user_id)return false;
		if($user_id < 100)return true;
		return false;
	}
}

?>