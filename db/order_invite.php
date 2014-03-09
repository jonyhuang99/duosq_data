<?php
//邀请奖励订单表操作基类，***订单相关操作出错必须throw Exception***
//子订单模块必须定义确认状态STATUS_PASS[到账网站]常量
namespace DB;

class OrderInvite extends _Db {

	var $name = 'OrderInvite';
	var $primaryKey = 'o_id'; //指定主键

	//扣款订单状态常量
	const STATUS_WAIT_CONFIRM = 0; //等待确认
	const STATUS_PASS = 1; //已到账[网站]
	const STATUS_INVALID = 10; //邀请奖励无效

	/**
	 * 新增用户邀请奖励订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据，扣款类型type(1:系统提现 2:购物订单无效 3:红包订单无效)
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['child_id']){
			throw new \Exception("[order_invite][o_id:{$o_id}][add][param error]");
		}

		$this->create();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$ret = parent::save($data);
		if(!$ret){
			throw new \Exception("[order_invite][o_id:{$o_id}][add][save error]");
		}
		return $ret;
	}

	/**
	 * 邀请奖励订单数据
	 * @param char    $o_id       子订单编号
	 * @param int     $new_field  新字段信息
	 */
	function update($o_id, $new_field, $force=false){

		//TODO，保护状态，无效状态不能重新激活

		if(!$o_id || !$new_field){
			throw new \Exception("[order_invite][o_id:{$o_id}][update][param error]");
		}

		$old_detail = $this->find(array('o_id'=>$o_id));

		if(!$old_detail){
			throw new \Exception("[order_invite][o_id:{$o_id}][update][o_id not exist]");
		}

		$new_field['o_id'] = $o_id;
		$ret = parent::save(arrayClean($new_field));

		if(!$ret){
			throw new \Exception("[order_invite][o_id:{$o_id}][update][save error]");
		}

		if(isset($new_field['status'])){
			clearTableName($old_detail);
			if($old_detail['status'] != $new_field['status']){
				$this->afterUpdateStatus($o_id, $old_detail['status'], $new_field['status']);
			}
		}
		return $ret;
	}

	//邀请奖励订单状态更新后，触发资产变化
	function afterUpdateStatus($o_id, $from, $to){

		$m_order = D('order')->detail($o_id);
		if(!$m_order){
			throw new \Exception("[order_invite][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//扣款订单状态由 待处理 => 已通过，进行账号扣款
		if($from == self::STATUS_WAIT_CONFIRM && $to == self::STATUS_PASS){
			//调整资产
			$ret = D('fund')->adjustBalanceForOrder($o_id);
			if(!$ret){
				throw new \Exception("[order_invite][o_id:{$o_id}][afterUpdateStatus][adjustBalanceForOrder error]");
			}
		}
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>