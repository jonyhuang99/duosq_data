<?php
//红包订单表操作基类，***订单相关操作出错必须throw Exception***
namespace DB;

class OrderCashgift extends _Db {

	var $name = 'OrderCashgift';
	var $primaryKey = 'o_id'; //指定主键

	/**
	 * 新增用户红包子订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据，红包类型(1:新人抽奖 2:新人任务 3:条件红包)
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['gifttype']){
			throw new \Exception("[order_cashgift][add][param error]");
		}

		$this->create();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$ret = parent::save($data);
		if(!$ret){
			throw new \Exception("[order_cashgift][add][save error]");
		}
		return $ret;
	}

	/**
	 * 更新红包订单状态
	 * @param char    $o_id     主订单编号
	 * @param int     $status   主订单初始状态
	 */
	function update($o_id, $status){

		//TODO，保护状态，无效状态不能重新激活
		$data = array('o_id'=>$o_id, 'status'=>$status);
		$ret = parent::save(arrayClean($data));

		if(!$ret){
			throw new \Exception("[order_cashgift][update][save error]");
		}
		return $ret;
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>