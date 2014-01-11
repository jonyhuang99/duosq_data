<?php
//DAL:知会用户模块
namespace DAL;

class Notify extends _Dal {

	const TYPE_ORDERBACK = 1;
	const TYPE_PAYMENTCOMPLETE = 2;

	//添加订单到账号通知任务
	function addOrderBackJob($o_id){

		$detail = D('order')->detail($o_id);
		if(!$detail)return;
		return $this->redis('notify')->addJob(self::TYPE_ORDERBACK, $detail['user_id'], $o_id);
	}

	//获取所有订单到账号通知任务
	function getOrderBackJobs(){

		return $this->redis('notify')->getJob(self::TYPE_ORDERBACK);
	}

	//添加支付成功通知任务
	function addPaymentCompleteJob($o_id){

		$detail = D('order')->detail($o_id);
		if(!$detail)return;
		return $this->redis('notify')->addJob(self::TYPE_PAYMENTCOMPLETE, $detail['user_id'], $o_id);
	}

	//获取所有支付成功通知任务
	function getPaymentCompleteJobs(){

		return $this->redis('notify')->getJob(self::TYPE_PAYMENTCOMPLETE);
	}
}
?>