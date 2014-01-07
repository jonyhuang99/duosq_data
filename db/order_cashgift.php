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
	const STATUS_INVALIDE = 10; //状态_无效

	//红包类型
	const GIFTTYPE_LUCK = 1; //新人抽奖送集分宝
	const GIFTTYPE_TASK = 2; //新人任务送集分宝
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
	function update($o_id, $new_field){

		//TODO，保护状态，无效状态不能重新激活

		if(!$o_id || !$new_field){
			throw new \Exception("[order_cashgift][o_id:{$o_id}][update][param error]");
		}
		$old_detail = $this->find(array('o_id'=>$o_id));

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

		//扣款订单状态由待处理 => 已通过，进行资产增加
		if($from == self::STATUS_WAIT_ACTIVE && $to == self::STATUS_PASS){
			D()->db('fund')->add($o_id, $m_order['user_id'], $m_order['cashtype'], $m_order['n'], $m_order['amount']);

			//标记自动打款
			D()->redis('queue')->addAutopayJob($m_order['cashtype'], $m_order['user_id']);
		}
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>