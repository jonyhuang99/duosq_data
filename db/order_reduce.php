<?php
//红包订单表操作基类，***订单相关操作出错必须throw Exception***
namespace DB;

class OrderReduce extends _Db {

	var $name = 'OrderReduce';
	var $primaryKey = 'o_id'; //指定主键

	const STATUS_WAIT_CONFIRM = 0; //等待确认
	const STATUS_PASS = 1; //已到账[网站]
	const STATUS_PAYING = 2; //正在支付中
	const STATUS_PAY_DONE = 3; //已打款
	const STATUS_PAY_ERROR = 4; //打款失败

	/**
	 * 新增用户扣款订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据，扣款类型typ(1:系统提现)
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['type']){
			throw new \Exception("[order_reduce][add][param error]");
		}

		$this->create();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$ret = parent::save($data);
		if(!$ret){
			throw new \Exception("[order_reduce][add][save error]");
		}
		return $ret;
	}

	/**
	 * 更新扣款订单状态
	 * @param char    $o_id     主订单编号
	 * @param int     $status   主订单初始状态
	 */
	function update($o_id, $status, $errcode=''){

		//TODO，保护状态，无效状态不能重新激活
		$data = array('o_id'=>$o_id, 'status'=>$status, 'errcode'=>$errcode);
		$ret = parent::save(arrayClean($data));

		if(!$ret){
			throw new \Exception("[order_reduce][update][save error]");
		}
		return $ret;
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>