<?php
//淘宝订单表操作基类，***订单相关操作出错必须throw Exception***
//子订单模块必须定义确认状态STATUS_PASS[到账网站]常量
namespace DB;

class OrderTaobao extends _Db {

	var $name = 'OrderTaobao';
	var $primaryKey = 'o_id'; //指定主键

	//淘宝订单状态常量
	const STATUS_WAIT_DEAL_DONE = 0; //等待订单成交
	const STATUS_WAIT_CONFIRM = 1; //等待确认
	const STATUS_WAIT_D1 = 5; //延期5天
	const STATUS_WAIT_D2 = 6; //延期14天
	const STATUS_WAIT_D3 = 7; //延期50天
	const STATUS_PASS = 10; //已到账[网站]
	const STATUS_INVALID = 20; //不通过

	//对方_订单状态
	const R_STATUS_CREATED = 0; //对方:订单创建
	const R_STATUS_PAYED = 1; //对方:订单付款
	const R_STATUS_FAILED = 2; //对方:订单失败
	const R_STATUS_INVALID = 3; //对方:订单失效
	const R_STATUS_COMPLETED = 10; //对方:订单结算

	/**
	 * 新增用户扣款订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据，扣款类型typ(1:系统提现)
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['r_orderid'] || !$data['r_id']){
			throw new \Exception("[order_taobao][o_id:{$o_id}][add][param error]");
		}

		$exist = $this->find(array('r_orderid'=>$data['r_orderid'], 'r_id'=>$data['r_id']));
		if($exist){
			throw new \Exception("[order_taobao][o_id:{$o_id}][add][r_orderid:{$data['r_orderid']} existed]");
		}

		$this->create();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$ret = parent::save($data);
		if(!$ret){
			throw new \Exception("[order_taobao][o_id:{$o_id}][add][save error]");
		}

		//更新淘宝店铺佣金表
		$ret2 = D()->db('shop_taobao')->save(array('shopname'=>$data['r_shopname'], 'wangwang'=>$data['r_wangwang'], 'yongjin_rate'=>$data['r_yongjin_rate']));

		if(!$ret2){
			throw new \Exception("[order_taobao][o_id:{$o_id}][add][save shop_taobao error]");
		}

		return $ret;
	}

	/**
	 * 更新扣款订单数据
	 * @param char    $o_id       子订单编号
	 * @param int     $new_field  新字段信息
	 */
	function update($o_id, $new_field){

		if(!$o_id || !$new_field){
			throw new \Exception("[order_taobao][o_id:{$o_id}][update][param error]");
		}
		$old_detail = $this->find(array('o_id'=>$o_id));

		$new_field['o_id'] = $o_id;
		$ret = parent::save(arrayClean($new_field));

		if(!$ret){
			throw new \Exception("[order_taobao][o_id:{$o_id}][update][save error]");
		}

		clearTableName($old_detail);

		//触发主状态变化，主状态在后台审核时会更新
		if(isset($new_field['status'])){

			if($old_detail['status'] != $new_field['status']){
				$trigger = $this->afterUpdateStatus($o_id, $old_detail['status'], $new_field['status'], $new_field);
				if($trigger) return $ret; //碰到触发规则，不再往下执行子订单状态变化
			}
		}

		//触发子状态更新，子状态在上传旧订单有可能更新，也可能触发主状态更新
		if(isset($new_field['r_status'])){

			if($old_detail['r_status'] != $new_field['r_status']){
				$trigger = $this->afterUpdateRStatus($o_id, $old_detail['r_status'], $new_field['r_status'], $new_field);
				if($trigger) return $ret;
			}
		}

		//TODO 订单金额变动，判断是否通过状态，以及是否有相应资产流水，多则补资产并触发打款，少则扣除，没资产变动则不变
		return $ret;
	}

	//淘宝订单状态更新后，触发资产变化、打款成功应发通知
	function afterUpdateStatus($o_id, $from, $to, $new_field){

		//订单主状态变为通过，判断是否已经打过款，增加资产，触发自动打款操作
		$m_order = D('order')->detail($o_id);

		if(!$m_order){
			throw new \Exception("[order_taobao][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//淘宝订单状态由待处理 => 已通过，进行账号增加流水
		if($from == self::STATUS_WAIT_CONFIRM && $to == self::STATUS_PASS){

			$ret = D('fund')->adjustBalanceForOrder($o_id);
			if(!$ret){
				throw new \Exception("[order_taobao][o_id:{$o_id}][afterUpdateStatus][adjustBalanceForOrder error]");
			}

			//主订单状态变为已通过
			D('order')->db('order')->update($o_id, \DAL\Order::STATUS_PASS);
			return true;
		}

		//淘宝订单状态由已通过 => 不通过，进行账号扣除流水，主订单变为不通过
		if($to == self::STATUS_INVALID){

			//有可能是afterUpdateRStatus传递过来，因此再次设置主状态
			$this->update($o_id, array('status'=>self::STATUS_INVALID));
			$money = D('fund')->getOrderBalance($o_id, $m_order['cashtype'], true);

			if($money > 0){
				D()->db('order_reduce');
				$errcode = '';

				$ret = D('fund')->reduceBalance($m_order['user_id'], \DB\OrderReduce::TYPE_ORDER, array('refer_o_id'=>$o_id, 'cashtype'=>$m_order['cashtype'], 'amount'=>$money), $errcode);

				if(!$ret){
					throw new \Exception("[order_taobao][o_id:{$o_id}][afterUpdateStatus][reduceBalance error]["._e($errcode, false)."]");
				}
			}

			//主订单状态变为不通过
			D('order')->db('order')->update($o_id, \DAL\Order::STATUS_INVALID);
			return true;
		}
	}

	function afterUpdateRStatus($o_id, $from, $to, $new_field){

		$m_order = D('order')->detail($o_id);
		if(!$m_order){
			throw new \Exception("[order_taobao][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//淘宝订单子状态由 其他状态 => 已成交，子订单主状态变为待审，修改主订单金额，资产操作为增加
		if( $to == self::R_STATUS_COMPLETED){

			D()->db('order')->updateFanli($o_id, $new_field['fanli']);
			$this->update($o_id, array('status'=>self::STATUS_WAIT_CONFIRM));
			return true;
		}

		//订单子状态变为无效/失败，判断是否有打款流水平衡，扣除多余部分，主状态变为不通过
		if( $to == self::R_STATUS_FAILED || $to == self::R_STATUS_INVALID ){
			$this->afterUpdateStatus($o_id, self::STATUS_PASS, self::STATUS_INVALID, $new_field);
			return true;
		}
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>