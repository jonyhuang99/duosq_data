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
	function jfb($user_id, &$errcode){

		if(!$user_id){
			$errcode = _e('sys_param_err');
			return false;
		}

		//获取集分宝余额
		$balance = D('fund')->getBalance($user_id, \DAL\Fund::CASHTYPE_JFB);

		if($balance <= 0){
			$errcode = _e('balance_not_enough');
			return false;
		}

		//生成扣款订单
		D()->db('order_reduce');
		$o_id = D('fund')->reduceBalance($user_id, \DB\OrderReduce::TYPE_SYSPAY, array('cashtype'=>\DAL\Fund::CASHTYPE_JFB, 'amount'=>$balance), $errcode);
		if(!$o_id){
			$errcode = _e('jfb_reduce_order_create_err');
			return false;
		}

		//从订单读出用户找出支付宝，以及打款数量
		$order_detail = D('order')->detail($o_id);
		if(!$order_detail){
			$errcode = _e('order_not_exist');
			D('log')->pay($o_id, 0, $errcode);
			return false;
		}

		$user_detail = D('user')->detail($order_detail['user_id']);
		if(!$user_detail){
			$errcode = _e('user_not_exist');
			D('log')->pay($o_id, 0, $errcode);
			return false;
		}

		$alipay = $user_detail['alipay'];
		$amount = $order_detail['amount'];

		//标识扣款订单为正在打款
		$ret = D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_PAYING));

		if(!$ret){
			$errcode = _e('jfb_reduce_order_lock_err');
			D('log')->pay($o_id, 0, $errcode, $alipay, \DAL\Fund::CASHTYPE_JFB, $amount);
			return false;
		}

		//调用接口打款
		$api_name = 'duoduo';
		$ret = $this->api($api_name)->pay($o_id, $alipay, $amount, $errcode);

		if($ret){
			$this->_afterPaymentSucc($o_id, $user_id, \DAL\Fund::CASHTYPE_JFB, $alipay, $amount, $api_name);
			$ret = array('amount'=>$amount, 'o_id'=>$o_id, 'alipay'=>$alipay, 'api_name'=>$api_name);
			return $ret;
		}else{
			$this->_afterPaymentFail($o_id, $user_id, \DAL\Fund::CASHTYPE_JFB, $alipay, $amount, $api_name, $errcode);
		}

		return false;
	}

	/**
	 * 除收款账号本身问题，外的打款失败进行重打尝试
	 * @param  [type] $o_id [description]
	 * @return [type]       [description]
	 */
	function jfbRetry($o_id){

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
			$ret = $this->api($api_name)->pay($o_id, $alipay, abs($amount), $errcode);

			if($ret){
				$this->_afterPaymentSucc($o_id, $o_detail['user_id'], \DAL\Fund::CASHTYPE_JFB, $alipay, $amount, $api_name);
				return true;
			}else{
				$this->_afterPaymentFail($o_id, $o_detail['user_id'], \DAL\Fund::CASHTYPE_JFB, $alipay, $amount, $api_name, $errcode);
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
	function _afterPaymentSucc($o_id, $user_id, $cashtype, $alipay, $amount, $api_name){

		//标识扣款订单为已打款
		D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_PAY_DONE));
		//更新支付宝验证信息
		D('user')->validAlipay($user_id, \DAL\User::ALIPAY_VALID_JFB);
		//触发打款完毕到账通知，在业务表层做，防止打款事务未完，提前通知
		D('notify')->addPaymentCompleteJob($o_id);

		D('log')->pay($o_id, 1, 0, $alipay, $cashtype, $amount, $api_name);
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
	function _afterPaymentFail($o_id, $user_id, $cashtype, $alipay, $amount, $api_name, $errcode){

		//标识扣款订单为打款不成功
		if($errcode == _e('jfb_account_nofound')){

			if($ret){//退款成功才进入支付宝错误状态
				D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_ALIPAY_ERROR));
			}

			//更新支付宝验证信息为无效状态
			D('user')->validAlipay($user_id, \DAL\User::ALIPAY_VALID_ERROR);

		}else{
			D('order')->updateSub('reduce', $o_id, array('status'=>\DB\OrderReduce::STATUS_PAY_ERROR));
		}

		D('log')->pay($o_id, 0, $errcode, $alipay, $cashtype, $amount, $api_name);

		return true;
	}

	/**
	 * 添加自动打款任务
	 * @param [type] $cashtype 资金类型
	 * @param [type] $user_id  用户ID
	 */
	function addAutopayJob($cashtype, $user_id){

		if(!$cashtype || !$user_id)return;
		return D()->redis('queue')->addAutopayJob($cashtype, $user_id);
	}

	/**
	 * 获取自动打款任务
	 * @param [type] $cashtype 资金类型
	 * return bigint           用户ID
	 */
	function getAutopayJob($cashtype){

		if(!$cashtype)return;
		return D()->redis('queue')->getAutopayJob($cashtype);
	}

	/**
	 * 完成自动打款任务
	 * @param [type] $cashtype 资金类型
	 * @param [type] $user_id  用户ID
	 */
	function doneAutopayJob($cashtype, $user_id){

		if(!$cashtype || !$user_id)return;
		return D()->redis('queue')->doneAutopayJob($cashtype, $user_id);
	}
}
?>