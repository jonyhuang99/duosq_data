<?php
//订单表操作基类，***订单相关操作出错必须throw Exception***
namespace DB;

class Order extends _Db {

	var $name = 'Order';
	var $primaryKey = 'o_id'; //指定主键

	//主订单表状态定义
	const STATUS_WAIT_CONFIRM = 0;
	const STATUS_PASS = 1;
	const STATUS_INVALID = 10;

	const CASHTYPE_JFB = 1; //资金类型：集分宝
	const CASHTYPE_CASH = 2; //资金类型：现金

	const N_ADD = 1; //增加资产
	const N_REDUCE = -1; //减少资产
	const N_ZERO = 0; //资产不变

	/**
	 * 新增用户主订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param int     $status   主订单初始状态
	 * @param string  $sub      子订单标识
	 * @param int     $cashtype 资金类型(1:集分宝 2:现金)
	 * @param int     $n        资产增减类型(-1:减少 1:增加)
	 * @param int     $amount   订单金额(单位:分)
	 * @param int     $is_show  是否显示在个人中心
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $status, $sub, $cashtype, $n, $amount, $is_show=1){

		if(!$o_id || !$user_id || !$sub || !$cashtype){
			throw new \Exception("[order:{$o_id}][add][param error]");
		}

		if($n && !$amount){ //允许平账订单
			throw new \Exception("[order:{$o_id}][add][param n&amount error]");
		}

		$data = array();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$data['status'] = $status;
		$data['sub'] = $sub;
		$data['cashtype'] = $cashtype;
		$data['n'] = $n;
		$data['amount'] = $amount;
		$data['is_show'] = $is_show;
		$ret = parent::add($data);

		if(!$ret){
			throw new \Exception("[order:{$o_id}][add][save error]");
		}
		return $ret;
	}

	/**
	 * 更新主订单状态
	 * @param char    $o_id     主订单编号
	 * @param int     $status   主订单初始状态
	 */
	function updateStatus($o_id, $status=''){

		$data = array('status'=>$status);

		$ret = parent::update($o_id, arrayClean($data));

		if(!$ret){
			throw new \Exception("[order:{$o_id}][update][save error]");
		}
		return $ret;
	}

	/**
	 * 更新主订单对应资金流水ID
	 * @param char    $o_id     主订单编号
	 * @param bigint  $fund_id  对应资产流水ID
	 */
	function updateFundId($o_id, $fund_id){

		if(!$o_id || !$fund_id)return;
		$data = array('fund_id'=>$fund_id);

		$ret = parent::update($o_id, arrayClean($data));
		if(!$ret){
			throw new \Exception("[order:{$o_id}][updateFundId][save error]");
		}
		return $ret;
	}

	/**
	 * 对于淘宝订单，待成交时未确认订单金额，因此当订单确认后，应更新真实的返利金额
	 * @return [type] [description]
	 */
	function updateFanli($o_id, $fanli=0){//有可能返利为0

		if(!$o_id)return;
		if($fanli){

			$old = $this->find(array('o_id'=>$o_id));
			$old = clearTableName($old);
			if($old['n']==0 && $old['amount']==0){
				$ret = parent::update($o_id, array('n'=>self::N_ADD, 'amount'=>$fanli));
			}else{
				$ret = parent::update($o_id, array('amount'=>$fanli));
			}
		}else{
			$ret = parent::update($o_id, array('n'=>self::N_ZERO, 'amount'=>0));
		}

		if(!$ret){
			throw new \Exception("[order:{$o_id}][updateFanli][save error]");
		}
		return $ret;
	}

	/**
	 * 更新主订单对应用户ID
	 * @param char    $o_id     主订单编号
	 * @param int     $user_id  对应用户ID
	 */
	function updateUserId($o_id, $user_id){
		if(!$o_id || !$user_id)return;
		$ret = parent::update($o_id, array('user_id'=>$user_id));

		if(!$ret){
			throw new \Exception("[order:{$o_id}][updateUserId][save error]");
		}
		return $ret;
	}
}
?>