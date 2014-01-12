<?php
//DAL:知会用户模块
namespace DAL;

class Notify extends _Dal {

	//通知类型
	const NOTIFYTYPE_ORDERBACK = 1;
	const NOTIFYTYPE_PAYMENTCOMPLETE = 2;

	//通知发送方式
	const SENDTYPE_EMAIL = 'email';
	const SENDTYPE_SMS = 'sms';

	//添加订单到账号通知任务
	function addOrderBackJob($o_id){

		$detail = D('order')->detail($o_id);
		if(!$detail)return;

		$sendtype = $this->_getSendtype($detail['user_id']);
		$ret = $this->redis('notify')->addJob(self::NOTIFYTYPE_ORDERBACK, $sendtype, $detail['user_id'], $o_id);

		if($ret){
			return $sendtype;
		}else{
			return false;
		}
	}

	//获取所有订单到账号通知任务
	function getOrderBackJobs($sendtype){

		if(!$sendtype)return;
		return $this->redis('notify')->getJob(self::NOTIFYTYPE_ORDERBACK, $sendtype);
	}

	//添加支付成功通知任务
	function addPaymentCompleteJob($o_id){

		$detail = D('order')->detail($o_id);
		if(!$detail)return;

		$sendtype = $this->_getSendtype($detail['user_id']);

		$ret = $this->redis('notify')->addJob(self::NOTIFYTYPE_PAYMENTCOMPLETE, $sendtype, $detail['user_id'], $o_id);

		if($ret){
			return $sendtype;
		}else{
			return false;
		}
	}

	//获取所有支付成功通知任务
	function getPaymentCompleteJobs($sendtype){

		if(!$sendtype)return;
		return $this->redis('notify')->getJob(self::NOTIFYTYPE_PAYMENTCOMPLETE, $sendtype);
	}

	//根据用户支付宝类型，返回通知方式
	private function _getSendtype($user_id){

		$alipay = D('user')->detail($user_id, 'alipay');
		if(!$alipay)return;
		$is_email = valid($alipay, 'email');
		$is_mobile = valid($alipay, 'mobile');

		if($is_email){
			$sendtype = self::SENDTYPE_EMAIL;
		}else if($is_mobile){
			$sendtype = self::SENDTYPE_SMS;
		}else{
			return;
		}
		return $sendtype;
	}
}
?>