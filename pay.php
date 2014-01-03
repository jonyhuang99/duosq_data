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
		$o_id = D('fund')->reduceBalance($user_id, \DAL\Fund::CASHTYPE_JFB, $balance, $errcode);
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
		$ret = $this->db('order_reduce')->update($o_id, \DAL\Order::REDUCE_STATUS_PAYING);

		if(!$ret){
			$errcode = _e('jfb_reduce_order_lock_err');
			D('log')->pay($o_id, 0, $errcode, $alipay, \DAL\Fund::CASHTYPE_JFB, $amount);
			return false;
		}

		//调用接口打款
		$api_name = 'duoduo';
		$ret = $this->api($api_name)->pay($o_id, $alipay, $amount, $errcode);

		//支付成功
		if($ret){
			//标识扣款订单为已打款
			if($this->db('order_reduce')->update($o_id, \DAL\Order::REDUCE_STATUS_PAY_DONE)){

				$ret = array('amount'=>$amount, 'o_id'=>$o_id, 'alipay'=>$alipay, 'api_name'=>$api_name);
				D('log')->pay($o_id, 1, 0, $alipay, \DAL\Fund::CASHTYPE_JFB, $amount, $api_name);
				return $ret;

			}else{

				$errcode = _e('jfb_payed_succ_order_reduce_update_err');
				D('log')->pay($o_id, 0, $errcode, $alipay, \DAL\Fund::CASHTYPE_JFB, $amount, $api_name);
				return false;
			}

		}else{
			//标识扣款订单为打款不成功
			if(!$this->db('order_reduce')->update($o_id, \DAL\Order::REDUCE_STATUS_PAY_ERROR, $errcode)){

				$errcode = _e('jfb_api_err');
				D('log')->pay($o_id, 0, $errcode, $alipay, \DAL\Fund::CASHTYPE_JFB, $amount, $api_name);
				return false;

			}else{

				$errcode = _e('jfb_payed_err_order_reduce_update_err');
				D('log')->pay($o_id, 0, $errcode, $alipay, \DAL\Fund::CASHTYPE_JFB, $amount, $api_name);
				return false;
			}
		}

		return false;
	}
}
?>