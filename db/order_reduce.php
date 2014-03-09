<?php
//扣款订单表操作基类，***订单相关操作出错必须throw Exception***
//子订单模块必须定义确认状态STATUS_PASS[到账网站]常量
namespace DB;

class OrderReduce extends _Db {

	var $name = 'OrderReduce';
	var $primaryKey = 'o_id'; //指定主键

	//扣款订单状态常量
	const STATUS_WAIT_CONFIRM = 0; //等待确认
	const STATUS_PASS = 1; //已到账[网站]
	const STATUS_PAYING = 2; //正在支付中
	const STATUS_PAY_DONE = 3; //已打款
	const STATUS_PAY_ERROR = 4; //打款失败[详情log_pay]
	const STATUS_ALIPAY_ERROR = 10; //打款失败[支付宝账户问题]

	const TYPE_SYSPAY = 1; //系统提现扣除订单
	const TYPE_ORDER = 2; //购物订单无效扣款
	const TYPE_CASHGIFT = 3; //现金红包订单无效扣款

	/**
	 * 新增用户扣款订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据，扣款类型type(1:系统提现 2:购物订单无效 3:红包订单无效)
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['type']){
			throw new \Exception("[order_reduce][o_id:{$o_id}][add][param error]");
		}

		$this->create();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$ret = parent::save($data);
		if(!$ret){
			throw new \Exception("[order_reduce][o_id:{$o_id}][add][save error]");
		}
		return $ret;
	}

	/**
	 * 更新扣款订单数据
	 * @param char    $o_id       子订单编号
	 * @param int     $new_field  新字段信息
	 */
	function update($o_id, $new_field, $force=false){

		//TODO，保护状态，无效状态不能重新激活

		if(!$o_id || !$new_field){
			throw new \Exception("[order_reduce][o_id:{$o_id}][update][param error]");
		}

		$old_detail = $this->find(array('o_id'=>$o_id));

		if(!$old_detail){
			throw new \Exception("[order_reduce][o_id:{$o_id}][update][o_id not exist]");
		}

		$new_field['o_id'] = $o_id;
		$ret = parent::save(arrayClean($new_field));

		if(!$ret){
			throw new \Exception("[order_reduce][o_id:{$o_id}][update][save error]");
		}

		if(isset($new_field['status'])){
			clearTableName($old_detail);
			if($old_detail['status'] != $new_field['status']){
				$this->afterUpdateStatus($o_id, $old_detail['status'], $new_field['status']);
			}
		}
		return $ret;
	}

	//扣款订单状态更新后，触发资产变化、打款成功应发通知
	function afterUpdateStatus($o_id, $from, $to){

		$m_order = D('order')->detail($o_id);
		if(!$m_order){
			throw new \Exception("[order_reduce][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//扣款订单状态由 待处理 => 已通过，进行账号扣款
		if($from == self::STATUS_WAIT_CONFIRM && $to == self::STATUS_PASS){
			//调整资产
			$ret = D('fund')->adjustBalanceForOrder($o_id);
			if(!$ret){
				throw new \Exception("[order_reduce][o_id:{$o_id}][afterUpdateStatus][adjustBalanceForOrder error]");
			}
		}

		//扣款订单状态由 打款中/打款失败 => 打款失败[支付宝账号错误]，需要退款
		if(($from == self::STATUS_PAYING || $from == self::STATUS_PAY_ERROR) && $to == self::STATUS_ALIPAY_ERROR){
			//进行退款
			$ret = D('fund')->refund($o_id);
			if(!$ret){
				throw new \Exception("[order_reduce][o_id:{$o_id}][afterUpdateStatus][refund error]");
			}
		}

		//标识正在打款时间，待重新打款不出现10分钟以内订单
		if($to == self::STATUS_PAYING){
			$ret = parent::save(array('o_id'=>$o_id, 'payingtime'=>date('Y-m-d H:i:s')));
			if(!$ret){
				throw new \Exception("[order_reduce][o_id:{$o_id}][afterUpdateStatus][payingtime update error]");
			}
		}
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>