<?php
//DAL:用户资金流水操作模块
namespace DAL;

class Fund extends _Dal {

	const CASHTYPE_JFB = 1; //资金类型：集分宝
	const CASHTYPE_CASH = 2; //资金类型：现金

	/**
	 * 获取指定用户的资产余额
	 * @param  bigint  $user_id  用户ID
	 * @param  int     $cashtype 获取资产类型，留空为全部
	 * @param  boolean $lock     是否锁定资产，如果是，使用完需及时解锁
	 * @return array             资产详情
	 */
	function getBalance($user_id, $cashtype=self::CASHTYPE_JFB, $lock=false){

		//TODO model底层加入group方法，直接返回计算好的数据
		//TODO 做资产锁，防止同时打款
		if(!$user_id)return;
		if($cashtype && ($cashtype != self::CASHTYPE_JFB && $cashtype != self::CASHTYPE_CASH))return;

		$fund_logs = $this->db('fund')->findAll(array('user_id'=>$user_id, 'cashtype'=>$cashtype));

		$ret = array(self::CASHTYPE_JFB=>0, self::CASHTYPE_CASH=>0);
		if($fund_logs){
			clearTableName($fund_logs);
			foreach($fund_logs as $fund){
				if($fund['n']==-1){
					$ret[$fund['cashtype']] -= $fund['amount'];
				}

				if($fund['n']==1){
					$ret[$fund['cashtype']] += $fund['amount'];
				}
			}
		}

		if($cashtype){
			return $ret[$cashtype];
		}
		return $ret;
	}

	/**
	 * 减少用户资产
	 * @param  bigint  $user_id  用户ID
	 * @param  int     $cashtype 获取资产类型，留空为全部
	 * @param  int     $amount   扣款金额(单位: 分)
	 * @param  string  &$errcode 出错码，定义code_err.php
	 * @return bool              是否执行成功
	 */
	function reduceBalance($user_id, $cashtype, $amount, &$errcode){

		if(!$user_id || !$cashtype || !$amount){
			$errcode = _e('sys_param_err');
			return false;
		}

		$max = $this->getBalance($user_id, $cashtype, true);

		if($max === false){
			$errcode = _e('balance_locked');
			return false;
		}

		if($max < $amount){
			$errcode = _e('balance_not_enough');
			return false;
		}

		D()->db('order_reduce');
		$ret = D('order')->add($user_id, \DAL\Order::STATUS_PASS, 'reduce', $cashtype, \DAL\Order::N_REDUCE, $amount, array('type'=>\DB\OrderReduce::TYPE_SYSPAY));

		$this->unlock($user_id);

		if(!$ret){
			$errcode = _e('sys_db_save_err');
			return false;
		}

		return $ret;
	}

	//资产解锁
	function unlock($user_id){
		return true;
	}
}
?>