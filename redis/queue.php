<?php
//队列操作底层

namespace REDIS;

class Queue extends _Redis {

	var $namespace = 'queue';
	var $dsn_type = 'database';

	const NOTIFY_TYPE_ORDERBACK = 1;
	const NOTIFY_TYPE_PAYMENTCOMPLETE = 2;

	/**
	 * 增加自动打款任务
	 * @param bigint  $user_id   用户ID
	 * @param int     $cashtype  打款类型(1:集分宝 2:现金)
	 */
	function addAutopayJob($cashtype, $user_id){

		if(!$user_id || !$cashtype)return;
		return $this->lpush('autopay:cashtype:'.$cashtype, $user_id);
	}

	/**
	 * 摘取自动打款任务，任务进入正在进行队列
	 * @param  int     $cashtype  打款类型(1:集分宝 2:现金)
	 * @return [type]             [description]
	 */
	function getAutopayJob($cashtype){

		if(!$cashtype)return;
		$ret = $this->brpoplpush('autopay:cashtype:'.$cashtype, 'autopay:cashtype:'.$cashtype.':paying', 30*60); //30分钟超时
		return $ret;
	}

	/**
	 * 完成任务后(无论成功失败)，删除任务进行中队列
	 * @param  [type] $cashtype [description]
	 * @param  [type] $user_id  [description]
	 * @return [type]           [description]
	 */
	function doneAutopayJob($cashtype, $user_id){

		if(!$cashtype || !$user_id)return;
		$ret = $this->lrem('autopay:cashtype:'.$cashtype.':paying', $user_id, -1);
	}
}
?>