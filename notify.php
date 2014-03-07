<?php
//DAL:知会用户模块
namespace DAL;

class Notify extends _Dal {

	//通知类型
	const NOTIFYTYPE_ORDERBACK = 1;
	const NOTIFYTYPE_PAYMENTCOMPLETE = 2;
	const NOTIFYTYPE_CASHGIFT_ACTIVED = 3;
	const NOTIFYTYPE_QUAN_REWARD_CREATED = 4;
	const NOTIFYTYPE_INVITE_STAT = 5;

	//通知发送方式
	const SENDTYPE_EMAIL = 'email';
	const SENDTYPE_SMS = 'sms';

	//添加订单到账号知会任务
	function addOrderBackJob($o_id){

		return $this->_addJob($o_id, self::NOTIFYTYPE_ORDERBACK);
	}

	//添加支付成功知会任务
	function addPaymentCompleteJob($o_id){

		return $this->_addJob($o_id, self::NOTIFYTYPE_PAYMENTCOMPLETE);
	}

	//添加现金红包激活成功知会任务
	function addCashgiftActivedJob($o_id){

		return $this->_addJob($o_id, self::NOTIFYTYPE_CASHGIFT_ACTIVED);
	}

	//有朋友圈红包产生推送知会任务
	function addQuanRewardCreatedJob($o_id){

		$user_id = D('order')->detail($o_id, 'user_id');
		$friends = D('friend')->getQuanFriends($user_id, false);

		if(!$friends)return;
		foreach($friends as $friend_id){
			$sendtype = $this->_getSendtype($friend_id);
			$this->redis('notify')->addJob(self::NOTIFYTYPE_QUAN_REWARD_CREATED, $sendtype, $friend_id, $o_id);
		}
	}

	//获取所有订单到账号知会任务
	function getOrderBackJobs($sendtype){

		if(!$sendtype)return;
		return $this->redis('notify')->getJob(self::NOTIFYTYPE_ORDERBACK, $sendtype);
	}

	//获取所有支付成功知会任务
	function getPaymentCompleteJobs($sendtype){

		if(!$sendtype)return;
		return $this->redis('notify')->getJob(self::NOTIFYTYPE_PAYMENTCOMPLETE, $sendtype);
	}

	//获取现金红包激活成功知会任务
	function getCashgiftActivedJobs($sendtype){

		if(!$sendtype)return;
		return $this->redis('notify')->getJob(self::NOTIFYTYPE_CASHGIFT_ACTIVED, $sendtype);
	}

	//获取朋友圈红包产生知会任务
	function getQuanRewardCreatedJob($sendtype){

		if(!$sendtype)return;
		return $this->redis('notify')->getJob(self::NOTIFYTYPE_QUAN_REWARD_CREATED, $sendtype);
	}

	//增加知会任务
	private function _addJob($o_id, $notifytype){

		if(!$o_id || !$notifytype)return false;
		$detail = D('order')->detail($o_id);
		if(!$detail || D('user')->sys($detail['user_id']))return;

		$sendtype = $this->_getSendtype($detail['user_id']);
		$ret = $this->redis('notify')->addJob($notifytype, $sendtype, $detail['user_id'], $o_id);

		if($ret){
			return $sendtype;
		}else{
			return false;
		}
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