<?php
//DAL:用户资金流水操作模块
namespace DAL;

class Fund extends _Dal {

	const CASHTYPE_JFB = 1; //资金类型：集分宝
	const CASHTYPE_CASH = 2; //资金类型：现金

	const N_ADD = 1; //增加资产
	const N_REDUCE = -1; //减少资产
	const N_ZERO = 0; //资产不变

	/**
	 * 获取指定用户的资产余额
	 * @param  bigint  $user_id  用户ID
	 * @param  int     $cashtype 获取资产类型，留空为全部
	 * @param  boolean $lock     是否锁定资产，如果是，使用完需及时解锁
	 * @return array             资产详情
	 */
	function getBalance($user_id, $cashtype=self::CASHTYPE_JFB, $lock=false){

		//TODO model底层加入group方法，直接返回计算好的数据
		//TODO 做资产锁，防止同时增减资产
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
	 * 获取指定订单的资产操作结果
	 * @param  bigint  $o_id     订单ID
	 * @param  int     $cashtype 获取资产类型，留空为全部
	 * @param  boolean $lock     是否锁定资产，如果是，使用完需及时解锁
	 * @return array             资产详情
	 */
	function getOrderBalance($o_id, $cashtype=self::CASHTYPE_JFB, $lock=false){

		//TODO model底层加入group方法，直接返回计算好的数据
		//TODO 做资产锁，防止同时增减资产
		if(!$o_id)return;
		if($cashtype && ($cashtype != self::CASHTYPE_JFB && $cashtype != self::CASHTYPE_CASH))return;

		$fund_logs = $this->db('fund')->findAll(array('o_id'=>$o_id, 'cashtype'=>$cashtype));

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
	 * 减少用户资产(该方法产生扣款订单，系统给用户打款，或者扣除某订单给错的资金)
	 * @param  bigint  $user_id     用户ID
	 * @param  int     $cashtype    扣除资产类型
	 * @param  int     $reduce_type 扣款业务类型(order_reduce db模块定义)
	 * @param  int     $amount      扣款金额(单位: 分)
	 * @param  string  &$errcode    出错码，定义code_err.php
	 * @return bool                 是否执行成功
	 */
	function reduceBalance($user_id, $reduce_type, $param, &$errcode=''){

		if(!$user_id || !$reduce_type){
			$errcode = _e('sys_param_err');
			return false;
		}

		D()->db('order_reduce');

		//系统自动打款，需传入资产类型，数量
		if($reduce_type == \DB\OrderReduce::TYPE_SYSPAY){
			if(!$param['cashtype'] || !$param['amount']){
				$errcode = _e('sys_param_err');
				return false;
			}

			$param['refer_o_id'] = '';
			$is_show = 1;
		}

		//扣除订单产生的资产，需传入扣除订单号，资产类型，数量
		if($reduce_type == \DB\OrderReduce::TYPE_ORDER){
			if(!$param['refer_o_id'] || !$param['cashtype'] || !$param['amount']){
				$errcode = _e('sys_param_err');
				return false;
			}
			$is_show = 0;
		}

		$max = $this->getBalance($user_id, $param['cashtype'], true);

		if($max === false){
			$errcode = _e('balance_locked');
			return false;
		}

		if($max < $param['amount']){
			$errcode = _e('balance_not_enough');
			return false;
		}

		D()->db('order_reduce');

		$ret = D('order')->add($user_id, \DAL\Order::STATUS_PASS, 'reduce', $param['cashtype'], \DAL\Order::N_REDUCE, $param['amount'], array('type'=>$reduce_type, 'refer_o_id'=>$param['refer_o_id']), $is_show);

		$this->unlock($user_id);

		if(!$ret){
			$errcode = _e('sys_db_save_err');
			return false;
		}

		return $ret;
	}

	/**
	 * 退回资产，只有打款失败[支付宝账户问题]情况下
	 * @param  [type] $o_id [description]
	 * @return [type]       [description]
	 */
	function refund($o_id){
		$m_order = D('order')->detail($o_id);
		$sub_status = D('order')->getSubDetail('reduce', $o_id, 'status');

		//只有打款失败[支付宝账户问题]情况下
		if($sub_status != \DB\OrderReduce::STATUS_ALIPAY_ERROR)return false;

		$amount = $this->getOrderBalance($o_id, $m_order['cashtype'], true);
		if($amount < 0){
			$found_id = D()->db('fund')->add($o_id, $m_order['user_id'], $m_order['cashtype'], self::N_ADD, abs($amount));
			$this->unlock($m_order['user_id']);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 根据订单金额调整用户资产(可用于增/减，适用于订单确认后正式操作用户资产)
	 * @param [type] $user_id [description]
	 * @param [type] $o_id    [description]
	 */
	function adjustBalanceForOrder($o_id){

		if(!$o_id)return;

		$m_order = D('order')->detail($o_id);
		$money = $this->getOrderBalance($o_id, $m_order['cashtype'], true);
		$prepare = $m_order['n'] * $m_order['amount'] - $money;

		if($prepare != 0){
			$n =  $prepare < 0? self::N_REDUCE: self::N_ADD;
			$found_id = D()->db('fund')->add($o_id, $m_order['user_id'], $m_order['cashtype'], $n, abs($prepare));

			$this->unlock($m_order['user_id']);
			return $found_id;
		}

		return true;
	}

	//资产解锁
	function unlock($user_id){
		return true;
	}

}
?>