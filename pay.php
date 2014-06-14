<?php
//DAL:向用户进行支付模块
namespace DAL;

class Pay extends _Dal {

	/**
	 * 向用户支付集分宝
	 * @param  char  $user_id   用户ID
	 * @param  int   $errcode   错误码(见code_err.php)
	 * @return bool             支付结果(失败:false $errcode)(成功: array('o_id','amount','alipay'))
	 */
	function jfb($user_id, &$errcode='', $force=false){

		if(!$user_id || D('user')->sys($user_id)){
			$errcode = _e('sys_param_err');
			return false;
		}

		//获取集分宝余额
		$balance = D('fund')->getBalance($user_id, \DAL\Fund::CASHTYPE_JFB);

		if($balance <= 0){
			$errcode = _e('balance_not_enough');
			return false;
		}

		$user_detail = D('user')->detail($user_id, false, false);
		if(!$user_detail){
			$errcode = _e('user_not_exist');
			D('log')->pay($o_id, 0, $errcode);
			return false;
		}

		//支付宝账号有问题，不打款
		if($user_detail['alipay_valid'] == \DAL\User::ALIPAY_VALID_ERROR && !$force){
			$errcode = _e('jfb_account_nofound');
			return false;
		}

		$alipay = $user_detail['alipay'];

		//生成扣款订单
		D()->db('order_reduce');
		$o_id = D('fund')->reduceBalance($user_id, \DB\OrderReduce::TYPE_SYSPAY, array('cashtype'=>\DAL\Fund::CASHTYPE_JFB, 'amount'=>$balance), $errcode);
		if(!$o_id){
			$errcode = _e('jfb_reduce_order_create_err');
			return false;
		}

		//标识扣款订单为正在打款
		$ret = D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_PAYING));

		if(!$ret){
			$errcode = _e('jfb_reduce_order_lock_err');
			D('log')->pay($o_id, 0, $errcode, $alipay, \DAL\Fund::CASHTYPE_JFB, $amount);
			return false;
		}

		//调用接口打款
		$api_name = 'duoduo';
		$errcode = 0;
		$api_ret = '';
		if($balance){ //0返利直接通过
			$ret = $this->api($api_name)->pay($o_id, $alipay, $balance, $errcode, $api_ret);
		}else{
			$ret = true;
		}

		//重复打款也算成功
		if($ret || $errcode == _e('jfb_trade_repeat')){

			//标记打过款了
			D('speed')->payJfb($user_id);

			$this->_afterPaymentSucc($o_id, $user_id, \DAL\Fund::CASHTYPE_JFB, $alipay, $balance, $api_name, $api_ret);
			$ret = array('amount'=>$balance, 'o_id'=>$o_id, 'alipay'=>$alipay, 'api_name'=>$api_name);
			return $ret;
		}else{
			$this->_afterPaymentFail($o_id, $user_id, \DAL\Fund::CASHTYPE_JFB, $alipay, $balance, $api_name, $errcode, $api_ret);
		}

		return false;
	}

	/**
	 * 向用户支付现金
	 * @param  char  $user_id   用户ID
	 * @param  int   $errcode   错误码(见code_err.php)
	 * @return bool             支付结果(失败:false $errcode)(成功: array('o_id','amount','alipay'))
	 */
	function cash($user_id, &$errcode){//TODO 改成daemon调用支付宝API自动打款

		if(!$user_id || D('user')->sys($user_id)){
			$errcode = _e('sys_param_err');
			return false;
		}

		//获取现金余额，现金需大于10元
		$balance = D('fund')->getBalance($user_id, \DAL\Fund::CASHTYPE_CASH);

		if($balance < 1000){
			$errcode = _e('balance_not_enough_1000_cash');
			return false;
		}

		$user_detail = D('user')->detail($user_id);
		if(!$user_detail){
			$errcode = _e('user_not_exist');
			D('log')->pay($o_id, 0, $errcode);
			return false;
		}

		$alipay = $user_detail['alipay'];

		//生成扣款订单
		D()->db('order_reduce');
		$o_id = D('fund')->reduceBalance($user_id, \DB\OrderReduce::TYPE_SYSPAY, array('cashtype'=>\DAL\Fund::CASHTYPE_CASH, 'amount'=>$balance), $errcode);
		if(!$o_id){
			$errcode = _e('cash_reduce_order_create_err');
			return false;
		}

		//状态为已打款
		$this->_afterPaymentSucc($o_id, $user_id, \DAL\Fund::CASHTYPE_CASH, $alipay, $balance, 'alipay_manual');

		$ret = array('amount'=>$balance, 'o_id'=>$o_id, 'alipay'=>$alipay, 'api_name'=>'alipay_manual');
		return $ret;
	}

	/**
	 * 除收款账号本身问题，外的打款失败进行重打尝试
	 * @param  [type] $o_id [description]
	 * @return [type]       [description]
	 */
	function jfbRetry($o_id, &$err){

		//确保已经足额扣款
		$o_detail = D('order')->detail($o_id);
		$alipay = D('user')->detail($o_detail['user_id'], 'alipay');
		if(!$o_detail || !$alipay)return false;

		$amount = D('fund')->getOrderBalance($o_id, \DAL\Fund::CASHTYPE_JFB);

		if($amount < 0 && abs($amount) == $o_detail['amount']){

			D()->db('order_reduce');

			//标识扣款订单为正在打款
			$ret = D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_PAYING));

			//调用接口打款
			$api_name = 'duoduo';
			$errcode = 0;
			$api_ret = '';
			$ret = $this->api($api_name)->pay($o_id, $alipay, abs($amount), $errcode, $api_ret);

			//重复打款也算成功
			if($ret || $errcode == _e('jfb_trade_repeat')){
				$this->_afterPaymentSucc($o_id, $o_detail['user_id'], \DAL\Fund::CASHTYPE_JFB, $alipay, $o_detail['amount'], $api_name, $api_ret);
				return true;
			}else{
				$this->_afterPaymentFail($o_id, $o_detail['user_id'], \DAL\Fund::CASHTYPE_JFB, $alipay, $o_detail['amount'], $api_name, $errcode, $api_ret);

				if($errcode && $errcode != _e('jfb_api_err')){
					$err = _e($errcode, false) . '||' . $api_ret;
				}else{
					$err = $api_ret;
				}
			}
		}

		return false;
	}

	/**
	 * 打款成功后续动作
	 * @param  [type] $cashtype [description]
	 * @param  [type] $alipay   [description]
	 * @param  [type] $amount   [description]
	 * @param  [type] $api_name [description]
	 * @param  [type] $ret      [description]
	 * @param  [type] $errcode  [description]
	 * @return [type]           [description]
	 */
	function _afterPaymentSucc($o_id, $user_id, $cashtype, $alipay, $amount, $api_name, $api_ret=''){

		//标识扣款订单为已打款
		D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_PAY_DONE));
		//更新支付宝验证信息
		D('user')->validAlipay($user_id, \DAL\User::ALIPAY_VALID_JFB);

		D('log')->pay($o_id, 1, 0, $alipay, $cashtype, $amount, $api_name, $api_ret);

		//触发打款完毕到账通知，在业务表层做，防止打款事务未完，提前通知
		D('notify')->addPaymentCompleteJob($o_id);

		return true;
	}

	/**
	 * 打款失败后续动作
	 * @param  [type] $o_id     [description]
	 * @param  [type] $cashtype [description]
	 * @param  [type] $alipay   [description]
	 * @param  [type] $amount   [description]
	 * @param  [type] $api_name [description]
	 * @param  [type] $errcode  [description]
	 * @return [type]           [description]
	 */
	function _afterPaymentFail($o_id, $user_id, $cashtype, $alipay, $amount, $api_name, $errcode, $api_ret=''){

		//标识扣款订单为打款不成功
		if($errcode == _e('jfb_account_nofound')){

			//更新订单状态为支付宝错误导致打款失败
			D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_ALIPAY_ERROR));

			//更新支付宝验证信息为无效状态
			D('user')->validAlipay($user_id, \DAL\User::ALIPAY_VALID_ERROR);

		}else{
			D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_PAY_ERROR));
		}

		D('log')->pay($o_id, 0, $errcode, $alipay, $cashtype, $amount, $api_name, $api_ret);

		return true;
	}

	/**
	 * 增加待支付现金用户
	 * @param [type] $user_id  用户ID
	 */
	function addWaitPaycash($user_id, $tag, $amount){//TODO 改成进入自动打款任务队列

		if(!$user_id)return false;
		$exist = $this->db('wait_paycash')->find(array('user_id'=>$user_id));
		if($exist){
			clearTableName($exist);
			$d = unserialize($exist['detail']);
			$d[$tag] = intval(@$d[$tag]) + $amount;
			$amount = $amount + intval($exist['amount']);
			$this->db('wait_paycash')->save(array('id'=>$exist['id'], 'detail'=>serialize($d), 'amount'=>$amount));
			return true;
		}

		$this->db('wait_paycash')->create();
		return $this->db('wait_paycash')->save(array('user_id'=>$user_id, 'detail'=>serialize(array($tag=>$amount)), 'amount'=>$amount));
	}

	/**
	 * 删除待支付现金用户
	 * @param [type] $user_id  用户ID
	 */
	function doneWaitPaycash($user_id){//TODO 改成进入自动打款任务队列

		if(!$user_id)return false;
		$id = $this->db('wait_paycash')->field('id', array('user_id'=>$user_id));
		if($id && $this->db('wait_paycash')->delete($id)){
			return true;
		}
		return false;
	}
}
?>