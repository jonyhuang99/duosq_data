<?php
//红包订单表操作基类，***订单相关操作出错必须throw Exception***
//子订单模块必须定义确认状态STATUS_PASS[到账网站]常量
namespace DB;

class OrderCashgift extends _Db {

	var $name = 'OrderCashgift';
	var $primaryKey = 'o_id'; //指定主键

	//红包订单表状态定义

	//红包状态
	const STATUS_WAIT_ACTIVE = 0; //状态_待激活
	const STATUS_PASS = 1; //状态_通过
	const STATUS_INVALID = 10; //状态_无效

	//红包类型
	const GIFTTYPE_LUCK = 1; //新人抽奖送集分宝
	const GIFTTYPE_TASK = 2; //新人任务送集分宝
	const GIFTTYPE_QUAN = 3; //省钱圈抢红包
	const GIFTTYPE_COND_10 = 5; //条件现金红包10元
	const GIFTTYPE_COND_20 = 6; //条件现金红包20元
	const GIFTTYPE_COND_50 = 8; //条件现金红包50元
	const GIFTTYPE_COND_100 = 9; //条件现金红包100元

	/**
	 * 新增用户红包子订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据，红包类型(1:新人抽奖 2:新人任务 3:条件红包)
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['gifttype']){
			throw new \Exception("[order_cashgift][o_id:{$o_id}][add][param error]");
		}

		$this->create();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;

		$current = D('fund')->getShoppingBalance($user_id);
		$max_reach = intval($this->field('reach', array('user_id'=>$user_id), 'reach DESC'));

		switch ($data['gifttype']) {
			case self::GIFTTYPE_COND_10:
				$data['reach'] = $max_reach + $current + 1500; //增加15元台阶
				break;

			case self::GIFTTYPE_COND_20:
				$data['reach'] = $max_reach + $current + 3000; //增加30元台阶
				break;

			case self::GIFTTYPE_COND_50:
				$data['reach'] = $max_reach + $current + 7500; //增加75元台阶
				break;

			case self::GIFTTYPE_COND_100:
				$data['reach'] = $max_reach + $current + 15000; //增加150元台阶
				break;
		}

		$ret = parent::save($data);
		if(!$ret){
			throw new \Exception("[order_cashgift][o_id:{$o_id}][add][save error]");
		}
		return $ret;
	}

	/**
	 * 更新红包订单数据
	 * @param char    $o_id       主订单编号
	 * @param int     $new_field  新字段信息
	 */
	function update($o_id, $new_field, $force=false){

		//TODO，保护状态，无效状态不能重新激活

		if(!$o_id || !$new_field){
			throw new \Exception("[order_cashgift][o_id:{$o_id}][update][param error]");
		}
		$old_detail = $this->find(array('o_id'=>$o_id));

		if(!$old_detail){
			throw new \Exception("[order_cashgift][o_id:{$o_id}][update][o_id not exist]");
		}

		$new_field['o_id'] = $o_id;
		$ret = parent::save(arrayClean($new_field));

		if(!$ret){
			throw new \Exception("[order_cashgift][o_id:{$o_id}][update][save error]");
		}

		if(isset($new_field['status'])){
			clearTableName($old_detail);
			if($old_detail['status'] != $new_field['status']){
				$this->afterUpdateStatus($o_id, $old_detail['status'], $new_field['status']);
			}
		}

		return $ret;
	}

	//红包订单状态更新后，触发资产增加
	function afterUpdateStatus($o_id, $from, $to){

		$m_order = D('order')->detail($o_id);
		if(!$m_order){
			throw new \Exception("[order_cashgift][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//红包状态由待处理激活 => 已激活，进行资产增加
		if($from == self::STATUS_WAIT_ACTIVE && $to == self::STATUS_PASS){
			//调整资产
			$ret = D('fund')->adjustBalanceForOrder($o_id);
			if(!$ret){
				throw new \Exception("[order_cashgift][afterUpdateStatus][adjustBalanceForOrder][error]");
			}

			//主订单状态变为已通过
			D('order')->updateStatus($o_id, \DAL\Order::STATUS_PASS);

			if(!$ret){
				throw new \Exception("[order_cashgift][o_id:{$o_id}][m_order][update status error]");
			}

			return true;
		}

		//红包由已通过 => 不通过，进行账号扣除流水，主订单变为不通过
		if($to == self::STATUS_INVALID){

			$errcode = '';
			$ret = D('fund')->reduceBalanceForOrder($o_id, $errcode);
			if(!$ret){
				throw new \Exception("[order_cashgift][o_id:{$o_id}][afterUpdateStatus][reduceBalanceForOrder error]["._e($errcode, false)."]");
			}

			//主订单状态变为不通过
			D('order')->updateStatus($o_id, \DAL\Order::STATUS_INVALID);
			return true;
		}
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>