<?php
//商城订单表操作基类，***订单相关操作出错必须throw Exception***
//子订单模块必须定义确认状态STATUS_PASS[到账网站]常量
namespace DB;

class OrderMall extends _Db {

	var $name = 'OrderMall';
	var $primaryKey = 'o_id'; //指定主键

	//商城订单状态常量
	const STATUS_WAIT_DEAL_DONE = 0; //等待订单成交
	const STATUS_WAIT_CONFIRM = 1; //等待确认
	const STATUS_WAIT_D1 = 5; //延期5天
	const STATUS_WAIT_D2 = 6; //延期14天
	const STATUS_WAIT_D3 = 7; //延期50天
	const STATUS_PASS = 10; //已到账[网站]
	const STATUS_INVALID = 20; //不通过

	//对方_订单状态
	const R_STATUS_CREATED = 0; //对方:订单创建
	const R_STATUS_INVALID = 1; //对方:订单无效
	const R_STATUS_UNKNOW = 9; //对方:订单状态未知
	const R_STATUS_COMPLETED = 10; //对方:订单结算

	/**
	 * 新增用户扣款订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['r_orderid']){
			throw new \Exception("[order_mall][error][o_id:{$o_id}][add][param error]");
		}

		//判断是否已存在
		$exist = $this->isExisted($data);

		if($exist){

			throw new \Exception("[order_mall][error][o_id:{$o_id}][add][r_orderid:{$data['r_orderid']} existed]");
		}

		$this->create();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$ret = parent::save($data);
		if(!$ret){
			throw new \Exception("[order_mall][error][o_id:{$o_id}][add]");
		}

		return $ret;
	}

	/**
	 * 更新扣款订单数据
	 * @param char    $o_id       子订单编号
	 * @param int     $new_field  新字段信息
	 */
	function update($o_id, $new_field, $force=false){

		if(!$o_id || !$new_field){
			throw new \Exception("[order_mall][error][o_id:{$o_id}][update][param error]");
		}
		$old_detail = $this->find(array('o_id'=>$o_id));

		if(!$old_detail){
			throw new \Exception("[order_mall][error][o_id:{$o_id}][update][o_id not exist]");
		}

		//用户ID变化，当主订单状态未打款前，都可以修正
		if(isset($new_field['user_id']) && ((!D('user')->sys($new_field['user_id']) && D('user')->sys($old_detail['user_id']))|| $force)){
			//修正主订单用户ID
			$main_status = D('order')->detail($o_id, 'status');
			if($main_status != \DAL\Order::STATUS_PASS || $force){
				D('order')->db('order')->updateUserid($o_id, $new_field['user_id']);
			}else{
				unset($new_field['user_id']);
			}
		}else if(isset($new_field['user_id'])){
			unset($new_field['user_id']);
		}

		$new_field['o_id'] = $o_id;
		$ret = parent::save(arrayClean($new_field));

		if(!$ret){
			throw new \Exception("[order_mall][error][o_id:{$o_id}][update]");
		}

		clearTableName($old_detail);

		//返利变化，及时更改订单返利，订单审核过了就不能再变，防止返利来回跳动
		//TODO实在是审核后在变化，需要走特殊通道调整
		if(isset($new_field['fanli'])){
			if($old_detail['fanli'] != $new_field['fanli'] && ($old_detail['status']!=self::STATUS_PASS && $old_detail['status']!=self::STATUS_INVALID)){
				D()->db('order')->updateFanli($o_id, $new_field['fanli']);

				//如果订单已返利，则进行订单资产平衡
				if($old_detail['status'] == self::STATUS_PASS){
					$ret = D('fund')->adjustBalanceForOrder($o_id);
				}
			}else{
				unset($new_field['fanli']);
			}
		}

		//触发主状态变化，主状态在后台审核时会更新
		if(isset($new_field['status'])){

			if($old_detail['status'] != $new_field['status']){
				$trigger = $this->afterUpdateStatus($o_id, $old_detail['status'], $new_field['status'], $old_detail, $force);
				if($trigger) return $ret; //碰到触发规则，不再往下执行子订单状态变化
			}
		}

		//触发子状态更新，子状态在上传旧订单有可能更新，也可能触发主状态更新
		if(isset($new_field['r_status'])){

			if($old_detail['r_status'] != $new_field['r_status']){
				$trigger = $this->afterUpdateRStatus($o_id, $old_detail['r_status'], $new_field['r_status'], $old_detail, $force);
				if($trigger) return $ret;
			}
		}

		//TODO 订单金额变动，判断是否通过状态，以及是否有相应资产流水，多则补资产并触发打款，少则扣除，没资产变动则不变
		return $ret;
	}

	//商城订单状态更新后，触发资产变化、打款成功应发通知
	function afterUpdateStatus($o_id, $from, $to, $old_detail, $force=false){

		//订单主状态变为通过，判断是否已经打过款，增加资产，触发自动打款操作
		$m_order = D('order')->detail($o_id);

		if(!$m_order){
			throw new \Exception("[order_mall][error][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//商城订单状态由 已通过 => 待审，不允许逆向，防止上传商城订单重置状态到待审核
		if(($from == self::STATUS_PASS && $to == self::STATUS_WAIT_CONFIRM) && $force){
			throw new \Exception("[order_mall][warn][o_id:{$o_id}][can not from STATUS_PASS to STATUS_WAIT_CONFIRM]");
		}

		//商城订单状态由待处理 => 已通过，进行账号增加流水
		if($from == self::STATUS_WAIT_CONFIRM && $to == self::STATUS_PASS){

			$ret = D('fund')->adjustBalanceForOrder($o_id);
			if(!$ret){
				throw new \Exception("[order_mall][error][o_id:{$o_id}][afterUpdateStatus][adjustBalanceForOrder error]");
			}

			//主订单状态变为已通过
			D('order')->updateStatus($o_id, \DAL\Order::STATUS_PASS);

			if(!$ret){
				throw new \Exception("[order_mall][error][o_id:{$o_id}][m_order][update status error]");
			}

			//判断如果达到10元，进入打款流程
			$amount = D('fund')->getBalance($m_order['user_id'], \DAL\Order::CASHTYPE_CASH);
			if($amount >= 1000){
				//加入待打款现金用户列表

				$amount = D('order')->detail($o_id, 'amount');
				D('pay')->addWaitPaycash($m_order['user_id'], '购物返钱', $amount);
			}

			//将上个月该渠道的等待订单，自动变为无效
			$timestamp = strtotime($old_detail['buydatetime']);
			if($old_detail['buydatetime'] && $timestamp > 0 && $timestamp < time()-DAY*20){
				$begin = date('Y-m-01', $timestamp - DAY*30);
				$end = date('Y-m-31', $timestamp - DAY*30);
				$all = D('order')->searchSubOrders('mall', "buydatetime >= '{$begin}' AND buydatetime <= '{$end}' AND status = 0 AND sp = '{$old_detail['sp']}'");
				foreach ($all as $o) {
					//主订单状态变为已通过
					D('order')->updateStatus($o['o_id'], \DAL\Order::STATUS_INVALID);
					parent::save(array('o_id'=>$o['o_id'], 'status'=>self::STATUS_INVALID, 'r_status'=>self::R_STATUS_INVALID));
				}
			}
			return true;
		}

		//不允许从其他状态变为通过
		if($from != self::STATUS_PASS && $to == self::STATUS_PASS){
			throw new \Exception("[order_mall][error][o_id:{$o_id}][m_order][can not from({$from}) to({$to})]");
		}

		//不允许更改不通过的订单
		if($from == self::STATUS_INVALID){
			throw new \Exception("[order_mall][error][o_id:{$o_id}][m_order][can not from({$from})]");
		}

		//商城订单状态 => 不通过，进行账号扣除流水，主订单变为不通过
		if($to == self::STATUS_INVALID){

			//有可能是afterUpdateRStatus传递过来，因此再次设置主状态
			$this->update($o_id, array('status'=>self::STATUS_INVALID));
			$errcode = '';
			$ret = D('fund')->reduceBalanceForOrder($o_id, $errcode);
			if(!$ret){
				throw new \Exception("[order_mall][error][o_id:{$o_id}][afterUpdateStatus][reduceBalanceForOrder error]["._e($errcode, false)."]");
			}

			//主订单状态变为不通过
			D('order')->updateStatus($o_id, \DAL\Order::STATUS_INVALID);
			return true;
		}
	}

	function afterUpdateRStatus($o_id, $from, $to, $old_detail){

		$m_order = D('order')->detail($o_id);
		if(!$m_order){
			throw new \Exception("[order_mall][error][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//不允许更改失效的订单
		if($from == self::R_STATUS_INVALID){
			throw new \Exception("[order_mall][afterUpdateStatus][sub_order][can not from({$from})]");
		}

		//商城订单子状态由 其他状态 => 已成交，子订单主状态变为待审，修改主订单金额，资产操作为增加
		if( $to == self::R_STATUS_COMPLETED){

			$this->update($o_id, array('status'=>self::STATUS_WAIT_CONFIRM));
			return true;
		}

		//订单子状态变为无效/失败，判断是否有打款流水平衡，扣除多余部分，主状态变为不通过
		if( $to == self::R_STATUS_INVALID){
			$this->afterUpdateStatus($o_id, self::STATUS_PASS, self::STATUS_INVALID, $old_detail);
			return true;
		}
	}

	/**
	 * 判断订单唯一性，较为复杂
	 * @param  array  $order  商城订单比对信息
	 * @return boolean        [description]
	 */
	function isExisted($order){

		if(!@$order['r_orderid'])return false;
		//商城订单ID有可能全部一样，每总商品一张订单，因此判断商品ID
		if($order['r_id']){
			$exist = D('order')->db('order_mall')->field('o_id', array('r_orderid'=>$order['r_orderid'], 'r_id'=>$order['r_id'], 'sp'=>$order['sp'], 'driver'=>$order['driver']));
		}else{
			//此处有可能订单ID相同，但没返回商品ID，需用排除法分析是否存在订单
			$sim_exist = D('order')->db('order_mall')->find(array('r_orderid'=>$order['r_orderid'], 'sp'=>$order['sp'], 'driver'=>$order['driver']));

			if(!$sim_exist){
				$exist = false;
			}else{
				clearTableName($sim_exist);
				//存在相同ID订单，但入库时间小于10秒(证明在同批列表)
				if(abs(strtotime($sim_exist['createtime']) - time()) < 10){
					$exist = false;
				//存在相同ID订单，下单时间不同
				}else if($sim_exist['buydatetime'] != $order['buydatetime']){
					$exist = false;
				}else{
					$tmp = D('order')->db('order_mall')->findAll(array('r_orderid'=>$order['r_orderid'], 'sp'=>$order['sp'], 'r_price'=>$order['r_price'], 'driver'=>$order['driver']));
					clearTableName($tmp);
					if(count($tmp) == 1){//当且仅当存在一个相同价格，相同对方订单ID的订单，该订单命中
						$exist = $tmp[0]['o_id'];
					}else{ //有可能价格变了(但已存在)，也有可能是对方后续才补的旧订单
						$exist = true;
					}
				}
			}
		}
		return $exist;
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>