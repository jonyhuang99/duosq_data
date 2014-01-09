<?php
//用户资产流水表操作基类，***资产相关操作出错必须throw Exception***
namespace DB;

class Fund extends _Db {

	var $name = 'Fund';

	/**
	 * 产生用户资产变动流水
	 * @param string $o_id     业务订单编号
	 * @param bigint $user_id  用户ID
	 * @param int $cashtype    资产类型(1:集分宝 2:现金)
	 * @param int $n           增减类型(-1:减少 1:增加)
	 * @param int $amount      资产变更数量(单位：分)
	 * return int              流水ID
	 */
	function add($o_id, $user_id, $cashtype, $n, $amount){

		if(!$o_id || !$user_id || !$cashtype ||!$n || !$amount){
			throw new \Exception("[fund][o_id{$o_id}][add][param error]");
		}

		$this->create();
		$data = array();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$data['cashtype'] = $cashtype;
		$data['n'] = $n;
		$data['amount'] = $amount;
		$ret = parent::save($data);

		if(!$ret){
			throw new \Exception("[fund][o_id{$o_id}][add][save error]");
		}

		//联动主订单fund_id更新
		$ret2 = D()->db('order')->updateFundId($o_id, $ret);

		if(!$ret2){
			throw new \Exception("[fund][o_id{$o_id}][add][save fund_id error]");
		}

		return $ret;
	}

	//置空save，只允许从add进入
	function save(){}
}
?>