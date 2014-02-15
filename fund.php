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
	 * @param  string  $sub      获取资产子订单类型，留空为全部
	 * @param  boolean $lock     是否锁定资产，如果是，使用完需及时解锁
	 * @return array             资产详情
	 */
	function getBalance($user_id, $cashtype='', $sub='', $lock=false){

		//TODO model底层加入group方法，直接返回计算好的数据
		//TODO 做资产锁，防止同时增减资产
		if(!$user_id)return;
		if($cashtype && ($cashtype != self::CASHTYPE_JFB && $cashtype != self::CASHTYPE_CASH))return;

		$fund_logs = $this->db('fund')->findAll(arrayClean(array('user_id'=>$user_id, 'cashtype'=>$cashtype, 'sub'=>$sub)));

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
	 * 获取用户购物资产总额
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	function getShoppingBalance($user_id){

		if(!$user_id)return false;

		//获取购物资产
		$balance = $this->getBalance($user_id, '', array('mall', 'taobao'));

		//减去购物订单无效扣款
		D()->db('order_reduce');
		$reduce_orders = D('order')->getSubList('reduce', array('user_id'=>$user_id, 'type'=>\DB\OrderReduce::TYPE_ORDER), '', '');

		$reduce_balance = array(self::CASHTYPE_JFB=>0, self::CASHTYPE_CASH=>0);
		if($reduce_orders){
			foreach ($reduce_orders as $order) {
				$b = $this->getOrderBalance($order['o_id']);

				if(isset($b[self::CASHTYPE_JFB])){
					$reduce_balance[self::CASHTYPE_JFB] += $b[self::CASHTYPE_JFB];
				}

				if(isset($b[self::CASHTYPE_CASH])){
					$reduce_balance[self::CASHTYPE_CASH] += $b[self::CASHTYPE_CASH];
				}
			}
		}

		$balance_sum = $reduce_balance[self::CASHTYPE_JFB] + $reduce_balance[self::CASHTYPE_CASH] + $balance[self::CASHTYPE_JFB] + $balance[self::CASHTYPE_CASH];

		return $balance_sum;
	}

	/**
	 * 获取用户邀请指定下线用户总共获得的提成
	 * @param  [type] $user_id  上游用户ID
	 * @param  [type] $child_id 下游用户ID
	 * @return [type]           [description]
	 */
	function getInviteRewardBalance($user_id, $child_id){

		$reward_orders = D('order')->getSubList('invite', array('user_id'=>$user_id, 'child_id'=>$child_id), '', '');

		$reward_balance = array(self::CASHTYPE_JFB=>0, self::CASHTYPE_CASH=>0);
		if($reward_orders){
			foreach ($reward_orders as $order) {
				$b = $this->getOrderBalance($order['o_id']);
				if(isset($b[self::CASHTYPE_JFB])){
					$reward_balance[self::CASHTYPE_JFB] += $b[self::CASHTYPE_JFB];
				}

				if(isset($b[self::CASHTYPE_CASH])){
					$reward_balance[self::CASHTYPE_CASH] += $b[self::CASHTYPE_CASH];
				}
			}
		}

		$balance_sum = $reward_balance[self::CASHTYPE_JFB] + $reward_balance[self::CASHTYPE_CASH];
		return $balance_sum;
	}

	/**
	 * 获取指定订单的资产操作结果
	 * @param  bigint  $o_id     订单ID
	 * @param  int     $cashtype 获取资产类型，留空为全部
	 * @param  boolean $lock     是否锁定资产，如果是，使用完需及时解锁
	 * @return array             资产详情
	 */
	function getOrderBalance($o_id, $cashtype='', $lock=false){

		//TODO model底层加入group方法，直接返回计算好的数据
		//TODO 做资产锁，防止同时增减资产
		if(!$o_id)return;
		if($cashtype && ($cashtype != self::CASHTYPE_JFB && $cashtype != self::CASHTYPE_CASH))return;

		$fund_logs = $this->db('fund')->findAll(arrayClean(array('o_id'=>$o_id, 'cashtype'=>$cashtype)));

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
	 * (注意：应在业务层，排除该方法重复调用的风险)
	 * @param  bigint  $user_id     用户ID
	 * @param  int     $cashtype    扣除资产类型
	 * @param  int     $reduce_type 扣款业务类型(order_reduce db模块定义)
	 * @param  int     $amount      扣款金额(单位: 分)
	 * @param  string  &$errcode    出错码，定义code_err.php
	 * @return string               扣款订单ID
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
		if($reduce_type == \DB\OrderReduce::TYPE_ORDER || $reduce_type == \DB\OrderReduce::TYPE_CASHGIFT){
			if(!$param['refer_o_id'] || !$param['cashtype'] || !$param['amount']){
				$errcode = _e('sys_param_err');
				return false;
			}
			$is_show = 0;
		}

		$max = $this->getBalance($user_id, $param['cashtype'], '', true);

		if($max === false){
			$errcode = _e('balance_locked');
			return false;
		}

		if($max < $param['amount']){
			$errcode = _e('balance_not_enough');
			return false;
		}

		$o_id = D('order')->addReduce($user_id, array_merge($param, array('type'=>$reduce_type)));

		$this->unlock($user_id);

		if(!$o_id){
			$errcode = _e('sys_db_save_err');
			return false;
		}

		//发送购物资产减少消息
		if($reduce_type == \DB\OrderReduce::TYPE_ORDER){
			$fund_id = D('order')->getSubDetail('reduce', $o_id, 'fund_id');
			if($fund_id)$this->sendMsg($fund_id, $m_order['user_id']);
		}

		return $o_id;
	}

	/**
	 * 根据已产生资产的订单，减少用户资产(该方法产生扣款订单)
	 * (注意：应在业务层，排除该方法重复调用的风险)
	 * @param  string  $o_id        目标订单ID
	 * @param  string  &$errcode    出错码，定义code_err.php
	 * @return bool                 是否执行成功
	 */
	function reduceBalanceForOrder($o_id, &$errcode=''){

		$m_order = D('order')->detail($o_id);
		if(!$m_order){
			$errcode = _e('order_not_exist');return false;
		}

		$money = D('fund')->getOrderBalance($o_id, $m_order['cashtype'], true);

		if($money > 0){

			D()->db('order_reduce');
			$errcode = '';
			if($m_order['sub'] == 'taobao' || $m_order['sub'] == 'mall'){
				$reduce_type = \DB\OrderReduce::TYPE_ORDER;
			}else if($m_order['sub'] == 'cashgift'){
				$reduce_type = \DB\OrderReduce::TYPE_CASHGIFT;
			}else{
				$errcode = _e('balance_reduce_type_error');
				return false;
			}

			$ret = $this->reduceBalance($m_order['user_id'], $reduce_type, array('refer_o_id'=>$o_id, 'cashtype'=>$m_order['cashtype'], 'amount'=>$money), $errcode);

			if(!$ret){
				$errcode = _e('balance_reduce_order_add_err');
				return false;
			}

		}
		return true;
	}

	/**
	 * 退回资产，只有打款失败[支付宝账户问题]等特殊情况下，增加退款订单
	 * @param  [type] $o_id        [description]
	 * @param  string &$errcode    出错码，定义code_err.php
	 * @return bool                是否执行成功
	 */
	function refund($o_id, &$errcode=''){

		if(!$o_id)return false;

		$m_order = D('order')->detail($o_id);
		if(!$m_order){
			$errcode = _e('order_not_exist');return false;
		}

		$sub_status = D('order')->getSubDetail('reduce', $o_id, 'status');

		D()->db('order_refund');
		$exist = D('order')->getSubList('refund', array('refer_o_id'=>$o_id, 'status'=>\DB\OrderRefund::STATUS_PASS));
		//不允许重复退款
		if($exist){
			$errcode = _e('refund_repeat');return false;
		}

		//只有打款失败[支付宝账户问题]情况下
		if($sub_status != \DB\OrderReduce::STATUS_ALIPAY_ERROR){
			$errcode = _e('refund_only_allow_alipay_invalid');return;
		}

		$amount = $this->getOrderBalance($o_id, $m_order['cashtype'], true);
		if($amount < 0){

			$sub_data = array();
			$sub_data['cashtype'] = $m_order['cashtype'];
			$sub_data['refer_o_id'] = $o_id;
			$sub_data['amount'] = abs($amount);

			$ret = D('order')->addRefund($m_order['user_id'], $sub_data);

			$this->unlock($m_order['user_id']);
			if($ret){
				$errcode = _e('refund_order_add_err');
				return true;
			}else{
				$errcode = _e('refund_refer_order_not_add_balance');
				return false;
			}
		}else{
			$errcode = _e('refund_refer_order_not_add_balance');
			return false;
		}
	}

	/**
	 * 根据订单金额平衡用户资产(可用于增/减，适用于订单确认后正式操作用户资产)
	 * @param [type] $user_id [description]
	 * @param [type] $o_id    [description]
	 * @return bool                是否执行成功
	 */
	function adjustBalanceForOrder($o_id){

		if(!$o_id)return false;

		$m_order = D('order')->detail($o_id);
		$money = $this->getOrderBalance($o_id, $m_order['cashtype'], true);
		$prepare = $m_order['n'] * $m_order['amount'] - $money;

		if($prepare != 0){
			$n =  $prepare < 0? self::N_REDUCE: self::N_ADD;
			$fund_id = D()->db('fund')->add($o_id, $m_order['user_id'], $m_order['sub'], $m_order['cashtype'], $n, abs($prepare));

			//发送购物资产变更消息
			$this->sendMsg($fund_id, $m_order['user_id']);
			$this->unlock($m_order['user_id']);
			return $fund_id;
		}

		return true;
	}

	//获取流水信息
	function detail($fund_id){

		if(!$fund_id)return false;
		$detail = $this->db('fund')->find(array('id'=>$fund_id));
		return clearTableName($detail);
	}

	//资产解锁
	function unlock($user_id){
		return true;
	}

	/**
	 * 发送购物资产变更消息
	 * @param  int    $fund_id    资产流水ID
	 * @param  [type] $user_id    用户ID
	 * @return bool               是否发送成功
	 */
	function sendMsg($fund_id, $user_id){

		if(!$fund_id || !$user_id || D('user')->sys($user_id))return;
		return D()->redis('queue')->add(\REDIS\Queue::KEY_BALANCE, $fund_id);
	}

	/**
	 * 获取购物资产变更消息
	 * @return string              资产流水ID
	 */
	function getMsg(){

		return D()->redis('queue')->bget(\REDIS\Queue::KEY_BALANCE);
	}

	/**
	 * 完成购物资产变更消息触发的任务
	 * @param  int    $fund_id    资产流水ID
	 * @return bool               是否执行成功
	 */
	function doneMsg($fund_id){

		if(!$fund_id)return;
		return D()->redis('queue')->done(\REDIS\Queue::KEY_BALANCE, $fund_id);
	}

}
?>