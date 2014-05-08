<?php
//各类业务锁专用底层
namespace REDIS;

class Lock extends _Redis {

	protected $namespace = 'lock';
	protected $dsn_type = 'database';

	const LOCK_QUAN_REWARD = 'quan_reward';
	const LOCK_CASHGIFT_ADD = 'cashgift_add';
	const LOCK_COUPON_ROB = 'coupon_rob';
	const LOCK_COUPON_ROB_NUM = 'coupon_rob_num';

	/**
	 * 获得一个业务锁
	 * @param  string $trade_type 业务类型
	 * @param  bigint $id         业务ID
	 * @return bool               是否成功获得锁
	 */
	function getlock($trade_type, $id){

		$expire = 5;//默认5秒锁
		if(!$trade_type || !$id)return false;

		switch ($trade_type) {
			case self::LOCK_QUAN_REWARD:
				$expire = 30;
				break;
			case self::LOCK_CASHGIFT_ADD:
				$expire = 10;
				break;
			case self::LOCK_COUPON_ROB_NUM:
				$id = $id.':day:'.date('d');
				$expire = DAY;
				break;
			case self::LOCK_COUPON_ROB:
				$expire = 60;
				break;
		}

		$ret = $this->setnx($trade_type.':id:'.$id, time());
		if(!$ret){
			return false; //锁被占用了
		}else{
			if($expire)
				$this->expire($trade_type.':id:'.$id, $expire);
			return true;
		}
	}

	/**
	 * 释放一个业务锁
	 * @param  string $trade_type 业务类型
	 * @param  bigint $id         业务ID
	 */
	function unlock($trade_type, $id){

		if($trade_type == self::LOCK_COUPON_ROB_NUM){
			$id = $id.':day:'.date('d');
		}
		$this->del($trade_type.':id:'.$id);
	}
}
?>