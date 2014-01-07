<?php
//订单表操作基类，***订单相关操作出错必须throw Exception***
namespace DB;

class Order extends _Db {

	var $name = 'Order';
	var $primaryKey = 'o_id'; //指定主键

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

		if(!$o_id || !$user_id || !$sub || !$cashtype || !$n || !$amount){
			throw new \Exception("[order:{$o_id}][add][param error]");
		}

		$this->create();
		$data = array();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$data['status'] = $status;
		$data['sub'] = $sub;
		$data['cashtype'] = $cashtype;
		$data['n'] = $n;
		$data['amount'] = $amount;
		$data['is_show'] = $is_show;
		$ret = parent::save($data);

		if(!$ret){
			throw new \Exception("[order:{$o_id}][add][save error]");
		}
		return $ret;
	}

	/**
	 * 更新主订单状态
	 * @param char    $o_id     主订单编号
	 * @param bigint  $fund_id  对应资产流水ID
	 * @param int     $status   主订单初始状态
	 */
	function update($o_id, $fund_id='', $status=''){

		$data = array('o_id'=>$o_id, 'fund_id'=>$fund_id, 'status'=>$status);

		$ret = $this->save(arrayClean($data));
		if(!$ret){
			throw new \Exception("[order:{$o_id}][update][save error]");
		}
		return $ret;
	}

	//置空save，只允许从add/update进入
	//function save(){}
}
?>