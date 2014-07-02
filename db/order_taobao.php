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
	const R_STATUS_INVALID = 3; //对方:订单失效
	const R_STATUS_COMPLETED = 10; //对方:订单结算

	/**
	 * 新增用户订单
	 * @param char    $o_id     主订单编号
	 * @param bigint  $user_id  用户ID
	 * @param array   $data     订单初始数据
	 * return char              主订单编号
	 */
	function add($o_id, $user_id, $data=array()){

		if(!$o_id || !$user_id || !$data['r_orderid'] || !$data['r_id']){
			throw new \Exception("[order_taobao][error][o_id:{$o_id}][add][param error]");
		}

		//由于存在r_orderid && r_id相同的订单，因此惟一性工作交由order_uplaod
		// $exist = $this->find(array('r_orderid'=>$data['r_orderid'], 'r_id'=>$data['r_id']));
		// if($exist){
		// 	throw new \Exception("[order_taobao][error][o_id:{$o_id}][add][r_orderid:{$data['r_orderid']} existed]");
		// }

		$this->create();
		$data['o_id'] = $o_id;
		$data['user_id'] = $user_id;
		$ret = parent::save($data);
		if(!$ret){
			throw new \Exception("[order_taobao][error][o_id:{$o_id}][add]");
		}

		//更新淘宝店铺佣金表
		$ret2 = D()->db('shop_taobao')->save(array('shopname'=>$data['r_shopname'], 'wangwang'=>$data['r_wangwang'], 'yongjin_rate'=>$data['r_yongjin_rate']));

		if(!$ret2){
			throw new \Exception("[order_taobao][error][o_id:{$o_id}][add][save shop_taobao error]");
		}

		return $ret;
	}

	/**
	 * 更新扣款订单数据
	 * @param char    $o_id       子订单编号
	 * @param int     $new_field  新字段信息
	 * @param force   $force      强制更新
	 */
	function update($o_id, $new_field, $force=false){

		if(!$o_id || !$new_field){
			throw new \Exception("[order_taobao][error][o_id:{$o_id}][update][param error]");
		}

		$old_detail = $this->find(array('o_id'=>$o_id));
		$old_detail = clearTableName($old_detail);

		if(!$old_detail){
			throw new \Exception("[order_taobao][error][o_id:{$o_id}][update][o_id not exist]");
		}

		//不允许更新额外返利比例历史
		unset($new_field['fanli_lv_rate']);

		//用户ID从系统号变化，当主订单状态未打款前，都可以修正
		//TODO 与chuser统一
		if(isset($new_field['user_id']) && $new_field['user_id']!=$old_detail['user_id'] && ((!D('user')->sys($new_field['user_id']) && D('user')->sys($old_detail['user_id'])) || $force)){
			//修正主订单用户ID
			$main_status = D('order')->detail($o_id, 'status');
			if($main_status != \DAL\Order::STATUS_PASS || $force){
				$ret = D('order')->db('order')->updateUserid($o_id, $new_field['user_id']);
				//删除旧的order_no关系
				D('user')->deleteTaobaoNo($old_detail['user_id'], getTaobaoNo($old_detail['r_orderid']));
				if(!D('user')->sys($new_field['user_id'])){
					//给新用户发送通知订单到了
					D('notify')->addOrderBackJob($o_id);
				}
			}else{
				unset($new_field['user_id']);
			}
		}else if(isset($new_field['user_id'])){
			unset($new_field['user_id']);
		}

		//返利变化，及时更改订单返利，订单审核过了就不能再变
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

		$new_field['o_id'] = $o_id;
		$ret = parent::save(arrayClean($new_field));

		if(!$ret){
			throw new \Exception("[order_taobao][error][o_id:{$o_id}][save]");
		}

		//监控佣金比例发生变化
		if($new_field['r_yongjin_rate'] > 0 && $old_detail['r_yongjin_rate'] != $new_field['r_yongjin_rate']){
			D('log')->action(1400, 1, array('data1'=>'taobao', 'data2'=>$o_id, 'data3'=>$old_detail['user_id'], 'data4'=>$old_detail['r_yongjin_rate'], 'data5'=>$new_field['r_yongjin_rate']));
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
				$trigger = $this->afterUpdateRStatus($o_id, $old_detail['r_status'], $new_field['r_status'], $old_detail);
				if($trigger) return $ret;
			}
		}

		//TODO 订单金额变动，判断是否通过状态，以及是否有相应资产流水，多则补资产并触发打款，少则扣除，没资产变动则不变
		return $ret;
	}

	//淘宝订单状态更新后，触发资产变化、打款成功应发通知
	function afterUpdateStatus($o_id, $from, $to, $old_detail, $force=false){

		//订单主状态变为通过，判断是否已经打过款，增加资产，触发自动打款操作
		$m_order = D('order')->detail($o_id);

		if(!$m_order){
			throw new \Exception("[order_taobao][error][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//淘宝订单状态由 已通过 => 待审，不允许逆向，防止上传淘宝订单重置状态到待审核
		if(($from == self::STATUS_PASS && $to == self::STATUS_WAIT_CONFIRM) && !$force){
			throw new \Exception("[order_taobao][warn][o_id:{$o_id}][can not from STATUS_PASS to STATUS_WAIT_CONFIRM]");
		}

		//淘宝订单状态由待处理 => 已通过，进行账号增加流水
		if($from == self::STATUS_WAIT_CONFIRM && $to == self::STATUS_PASS){

			$ret = D('fund')->adjustBalanceForOrder($o_id);
			if(!$ret){
				throw new \Exception("[order_taobao][error][o_id:{$o_id}][afterUpdateStatus][adjustBalanceForOrder]");
			}

			//主订单状态变为已通过
			$ret = D('order')->updateStatus($o_id, \DAL\Order::STATUS_PASS);

			if(!$ret){
				throw new \Exception("[order_taobao][error][o_id:{$o_id}][m_order][update status]");
			}

			//根据优惠券，翻倍返利，或者免单
			$coupon_detail = D('coupon')->isUsed($o_id, 'detail');
			if($coupon_detail){

				$coupon_type = $coupon_detail['type'];

				if($coupon_type == \DAL\Coupon::TYPE_DOUBLE){
					$amount = $old_detail['fanli'];
				}else if($coupon_type == \DAL\Coupon::TYPE_FREE_50){
					$amount = min(5000, $old_detail['r_price_deal'] - $old_detail['fanli']);
				}else if($coupon_type == \DAL\Coupon::TYPE_FREE_100){
					$amount = min(10000, $old_detail['r_price_deal'] - $old_detail['fanli']);
				}else if($coupon_type == \DAL\Coupon::TYPE_FREE_200){
					$amount = min(20000, $old_detail['r_price_deal'] - $old_detail['fanli']);
				}else if($coupon_type == \DAL\Coupon::TYPE_FREE_300){
					$amount = min(30000, $old_detail['r_price_deal'] - $old_detail['fanli']);
				}

				$sub_data = array();
				$sub_data['amount'] = $amount;
				$sub_data['coupon_id'] = $coupon_detail['id'];
				$sub_data['refer_o_id'] = $o_id;
				$ret = D('order')->addCoupon($old_detail['user_id'], $sub_data);

				if(!$ret){
					throw new \Exception("[order_taobao][error][o_id:{$o_id}][m_order][use coupon error]");
				}
			}

			return true;
		}

		//不允许从其他状态变为通过
		if($from != self::STATUS_WAIT_CONFIRM && $to == self::STATUS_PASS){
			throw new \Exception("[order_taobao][error][o_id:{$o_id}][m_order][can not from({$from}) to({$to})]");
		}

		//不允许更改不通过的订单
		if($from == self::STATUS_INVALID){
			throw new \Exception("[order_taobao][error][o_id:{$o_id}][m_order][can not from({$from})]");
		}

		//淘宝订单状态 => 不通过，进行账号扣除流水，主订单变为不通过
		if($to == self::STATUS_INVALID){

			$errcode = '';
			$ret = D('fund')->reduceBalanceForOrder($o_id, $errcode);
			if(!$ret){
				throw new \Exception("[order_taobao][o_id:{$o_id}][afterUpdateStatus][reduceBalanceForOrder error]["._e($errcode, false)."]");
			}

			//主订单状态变为不通过
			D('order')->updateStatus($o_id, \DAL\Order::STATUS_INVALID);
			return true;
		}
	}

	function afterUpdateRStatus($o_id, $from, $to, $old_detail){

		$m_order = D('order')->detail($o_id);
		if(!$m_order){
			throw new \Exception("[order_taobao][afterUpdateStatus][m_order:{$o_id} not found]");
		}

		//不允许更改失效的订单
		if($from == self::R_STATUS_INVALID){
			throw new \Exception("[order_taobao][afterUpdateStatus][sub_order][can not from({$from})]");
		}

		//淘宝订单子状态由 其他状态 => 已成交，子订单主状态变为待审，修改主订单金额，资产操作为增加
		if( $to == self::R_STATUS_COMPLETED){

			$this->update($o_id, array('status'=>self::STATUS_WAIT_CONFIRM));
			return true;
		}

		//订单子状态变为无效/失败，判断是否有打款流水平衡，扣除多余部分，主状态变为不通过
		if( $to == self::R_STATUS_INVALID ){

			$ret = parent::save(array('o_id'=>$o_id, 'status'=>self::STATUS_INVALID));
			$this->afterUpdateStatus($o_id, self::STATUS_PASS, self::STATUS_INVALID, $old_detail);
			return true;
		}
	}

	//置空save，只允许从add/update进入
	function save(){}
}
?>