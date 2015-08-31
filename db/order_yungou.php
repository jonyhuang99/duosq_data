<?php
//云购下注订单表操作基类，***订单相关操作出错必须throw Exception***
//子订单模块必须定义确认状态STATUS_PASS[到账网站]常量
namespace DB;

class OrderYungou extends _Db {

	var $name = 'OrderYungou';
	var $primaryKey = 'o_id'; //指定主键

	//扣款订单状态常量
	const STATUS_WAIT = 0; //待开奖
	const STATUS_PASS = 0; //待开奖，为了兼容order->add
	const STATUS_HIT = 1; //待领奖
	const STATUS_REWARD = 2; //已发奖
	const STATUS_MISS = 3; //没中奖
	const STATUS_INVALID = 4; //投注无效

	/**
	 * 新增用户下注订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['yungou_id']){
			throw new \Exception("[order_yungou][o_id:{$o_id}][add][param error]");
		}

		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$ret = parent::add($data);
		if(!$ret){
			throw new \Exception("[order_yungou][o_id:{$o_id}][add][save error]");
		}
		return $ret;
	}

	/**
	 * 更新下注订单数据
	 * @param char    $o_id       子订单编号
	 * @param int     $new_field  新字段信息
	 */
	function update($o_id, $new_field, $force=false){

		//TODO，保护状态，无效状态不能重新激活
		if(!$o_id || !$new_field){
			throw new \Exception("[order_yungou][o_id:{$o_id}][update][param error]");
		}

		$old_detail = $this->find(array('o_id'=>$o_id));

		if(!$old_detail){
			throw new \Exception("[order_yungou][o_id:{$o_id}][update][o_id not exist]");
		}

		$ret = parent::update($o_id, arrayClean($new_field));

		if(!$ret){
			throw new \Exception("[order_yungou][o_id:{$o_id}][update][save error]");
		}

		if(isset($new_field['status']) && $new_field['status'] == self::STATUS_PASS){
			//调整资产
			$ret = D('fund')->adjustBalanceForOrder($o_id);
			if(!$ret){
				throw new \Exception("[order_yungou][o_id:{$o_id}][afterUpdateStatus][adjustBalanceForOrder error]");
			}
		}

		return $ret;
	}

	/**
	 * 获取指定商品云购下注总量
	 * @param  [type] $yungou_id [description]
	 * @return [type]            [description]
	 */
	function getChipSum($yungou_id){

		$total = $this->findSum('amount', array('yungou_id'=>$yungou_id) );
		return intval($total);
	}
}
?>