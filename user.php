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
	function search($condition, $limit=10){

		if(!$limit){
			$ret = $this->db('user')->findAll($condition, '', 'id DESC');
		}else{
			$ret = $this->db('user')->findAll($condition, '', 'id DESC', $limit);
		}

		return clearTableName($ret);
	}

	//支付宝到用户ID的转换
	function Alipay2userid($alipay){
		if($alipay)
			return $this->db('user')->field('id', array('alipay'=>$alipay));
	}

	//获取用户昵称
	function getNickname($user_id, $short=false){

		if($this->sys($user_id))return '多省钱官网';
		$nickname = $this->detail($user_id, 'nickname');
		if($nickname){
			return $nickname;
		}else{
			return mask($this->detail($user_id, 'alipay'), 'alipay', 4, $short);
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

	//根据淘宝订单标识，拉出匹配的用户
	function getUserByTaobaoNo($no){

		if(!$no)return;
		$hit = $this->db('user_taobao')->findAll(array('taobao_no'=>$no));
		$user_ids = array();
		if($hit){
			clearTableName($hit);
			foreach($hit as $h){
				$user_ids[$h['user_id']] = 1;
			}
		}

		if($user_ids){
			return array_keys($user_ids);
		}
	}

	//找出用户淘宝订单标识
	function getTaobaoNo($user_id, $ex_valid=false){

		if(!$user_id)return;
		if($ex_valid){
			$hit = $this->db('user_taobao')->findAll(array('user_id'=>$user_id, 'valid'=>0));
		}else{
			$hit = $this->db('user_taobao')->findAll(array('user_id'=>$user_id));
		}

		$taobao_no = array();
		if($hit){
			clearTableName($hit);
			foreach($hit as $h){
				$taobao_no[] = $h['taobao_no'];
			}
		}

		return $taobao_no;
	}

	//删除用户对应的淘宝订单标识
	function deleteTaobaoNo($user_id, $taobao_no){

		if(!$user_id || !$taobao_no)return;
		return $this->db('user_taobao')->query("DELETE FROM user_taobao WHERE user_id = '{$user_id}' AND taobao_no = '{$taobao_no}'");
	}

	//增加用户对应淘宝订单标识
	function addTaobaoNo($user_id, $taobao_no){

		$exist = $this->db('user_taobao')->find(array('user_id'=>$user_id, 'taobao_no'=>$taobao_no));
		if(!$exist){
			$this->db('user_taobao')->create();
			return $this->db('user_taobao')->save(array('user_id'=>$user_id, 'taobao_no'=>$taobao_no));
		}
		return true;
	}

	//标识用户下过单
	function markUserHasOrder($user_id){
		$has_order = $this->detail($user_id, 'has_order');
		return $this->db('user')->update($user_id, array('has_order'=>$has_order+1));
	}

	//标识用户下过单
	function markUserCashgiftInvalid($user_id){
		return $this->db('user')->update($user_id, array('can_get_cashgift'=>0));
	}

	//标识用户为黑名单
	function markBlack($user_id, $status, $reason='reg_acttack'){
		if(!$status)return;
		if(is_array($user_id)){
			foreach($user_id as $id){
				//系统名单用户除外
				if(!$this->sys($id)){
					if(!$this->db('user')->find(array('id'=>$id, 'status'=>"> $status"))){
						$o_reason = $this-detail($id, 'reason');
						if($o_reason && stripos($o_reason, $reason)===false){
							$reason = $o_reason . ',' . $reason;
						}
						$ret = $this->db('user')->update($id, array('status'=>$status,'reason'=>$reason));
					}
				}
			}
		}else{
			if(!$this->sys($user_id)){
				if(!$this->db('user')->find(array('id'=>$user_id, 'status'=>"> $status"))){
					$o_reason = $this->detail($user_id, 'reason');
					if($o_reason && stripos($o_reason, $reason)===false){
						$reason = $o_reason . ',' . $reason;
					}
					$ret = $this->db('user')->update($user_id, array('status'=>$status,'reason'=>$reason));
				}
			}
		}
		return true;
	}

	//解除用户黑名单
	function unMarkBlack($user_id, $reason='has_order'){
		$detail = $this->detail($user_id);
		$o_reason = $detail['reason'];
		$status = $detail['status'];

		if($status < self::STATUS_BLACK_1)return true;

		if($o_reason && $reason != $o_reason){
			$reason = $o_reason . ',' . $reason;
		}
		$ret = $this->db('user')->update($user_id, array('status'=>1,'reason'=>$reason));
		return $ret;
	}

	//根据购物累计金额更新用户等级
	function updateLevel($user_id, $amount=0){

		if(!$user_id)return;
		$level = $this->detail($user_id, 'level');
		if($amount <= 10000){
			$new = 1;
		}else if($amount > 10000 && $amount <= 50000){
			$new = 2;
		}else if($amount > 50000 && $amount <= 100000){
			$new = 3;
		}else if($amount > 100000 && $amount <= 200000){
			$new = 4;
		}else if($amount > 200000){
			$new = 5;
		}

		$this->db('user')->update($user_id, array('level'=>$new));
		if($new > $level){
			return $new;
		}
	}

	//带上等级比例奖励
	function lvRate($user_id){

		if(!$user_id)return 0;
		$lv_rate = C('comm', 'lv_rate');
		$level = $this->detail($user_id, 'level');
		$lv_rate = intval($lv_rate[$level]);
		return $lv_rate;
	}

	//判断用户是否系统内部用户
	function sys($user_id){
		if(!$user_id)return false;
		if($user_id < 100)return true;
		return false;
	}

	//渲染用户购物频率
	function renderShopFre($user_id){
		$has_order = $this->detail($user_id, 'has_order');
		if($has_order == 0){
			return '☆';
		}else if($has_order == 1){
			return '☆☆☆';
		}else{
			return '☆☆☆☆☆';
		}
	}
}

?>