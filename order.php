<?php
//DAL:订单数据访问模块, ***订单/资产相关表操作必须catch Exception***
namespace DAL;

class Order extends _Dal {

	//主订单表状态定义
	const STATUS_WAIT_CONFIRM = 0;
	const STATUS_PASS = 1;
	const STATUS_INVALIDE = 10;

	const CASHTYPE_JFB = 1; //资金类型：集分宝
	const CASHTYPE_CASH = 2; //资金类型：现金

	const N_ADD = 1; //增加资产
	const N_REDUCE = -1; //减少资产

	const CASHGIFT_GIFTTYPE_LUCK = 1; //红包订单：新人抽奖送集分宝
	const CASHGIFT_GIFTTYPE_TASK = 2; //红包订单：新人任务送集分宝
	const CASHGIFT_GIFTTYPE_COND_10 = 5; //红包订单：条件现金红包10元
	const CASHGIFT_GIFTTYPE_COND_20 = 6; //红包订单：条件现金红包20元
	const CASHGIFT_GIFTTYPE_COND_50 = 8; //红包订单：条件现金红包50元
	const CASHGIFT_GIFTTYPE_COND_100 = 9; //红包订单：条件现金红包100元

	const CASHGIFT_STATUS_WAIT_ACTIVE = 0; //红包订单：状态_待激活
	const CASHGIFT_STATUS_PASS = 1; //红包订单：状态_通过
	const CASHGIFT_STATUS_INVALIDE = 10; //红包订单：状态_无效

	//资产扣除订单常量
	const REDUCE_TYPE_SYSPAY = 1; //系统提现
	const REDUCE_STATUS_WAIT_CONFIRM = 0; //等待确认
	const REDUCE_STATUS_PASS = 1; //已到账[网站]
	const REDUCE_STATUS_PAYING = 2; //正在支付中
	const REDUCE_STATUS_PAY_DONE = 3; //已打款
	const REDUCE_STATUS_PAY_ERROR = 4; //打款失败

	/**
	 * 获取用户订单列表(主订单数据)
	 * @param  bigint  $user_id   用户ID
	 * @param  object  $pn        分页组件对象
	 * @param  mix     $status    订单状态(默认全部，支持数组指定多个状态)
	 * @param  string  $sub       子订单标识(默认全部)
	 * @param  integer $show      每页显示几条
	 * @param  integer $maxPages  最大页数
	 * return  array              订单数据
	 */
	function get($user_id, $pn, $status='', $sub='', $show = 20, $maxPages = 10) {

		if(!$user_id)return;
		$condition['user_id'] = $user_id;
		$condition['status'] = $status;
		$condition['sub'] = $sub;
		$condition = arrayClean($condition);

		//page = 0 返回总页数
		$pn->show = $show;
		$pn->sortBy = 'createtime';
		$pn->direction = 'desc';
		$pn->maxPages = $maxPages;


		list($order, $limit, $page) = $pn->init($condition, array('modelClass' => $this->db('order')));

		$result = $this->db('order')->findAll($condition, '', $order, $limit, $page);
		//TODO联合子表查出子表状态

		$result = $this->_renderStatus(clearTableName($result));
		return $result;
	}

	//获取单独订单详情
	function detail($o_id){
		if(!$o_id)return;
		$ret = $this->db('order')->find(array('o_id'=>$o_id));
		return clearTableName($ret);
	}

	/**
	 * 获取用户子订单列表
	 * @param  bigint  $user_id   用户ID
	 * @param  string  $sub       子订单标识(默认全部)
	 * @param  mix     $status    订单状态(默认全部，支持数组指定多个状态)
	 * @param  array   $extra     子订单额外筛选项
	 * @return array              订单列表
	 */
	function getSub($user_id, $sub, $status='', $extra=array()){

		if(!$user_id || !$sub)return;
		$extra['status'] = $status;
		$extra['user_id'] = $user_id;

		$ret = $this->db('order_'.$sub)->findAll(arrayClean($extra));
		return clearTableName($ret);
	}

	/**
	 * 新增用户子订单&主订单，如果主订单状态为已到账，则新增用户资产流水
	 * @param bigint  $user_id  用户ID
	 * @param int     $status   主订单初始状态，状态常量定义见开篇
	 * @param string  $sub      子订单标识
	 * @param int     $cashtype 资金类型(1:集分宝 2:现金)
	 * @param int     $n        资产增减类型(-1:减少 1:增加)
	 * @param int     $amount   订单金额(单位:分)
	 * @param array   $sub_data 子订单初始值(参见各子订单db层)
	 * @param int     $is_show  是否显示在个人中心
	 * return char              主订单号
	 */
	function add($user_id, $status, $sub, $cashtype, $n, $amount, $sub_data, $is_show=1){

		if(!$user_id || !$sub || !$cashtype || !$n || !$amount || !$sub_data){
			return;
		}

		if($cashtype != self::CASHTYPE_JFB && $cashtype != self::CASHTYPE_CASH){
			return;
		}

		$this->db()->begin();
		try{

			$o_id = $this->redis('order')->createId();
			$this->db('order')->add($o_id, $user_id, $status, $sub, $cashtype, $n, $amount, $is_show);
			$this->db('order_'.$sub)->add($o_id, $user_id, $sub_data);

			//确认状态的订单，增加会员资产
			if($status == self::STATUS_PASS){

				$fund_id = $this->db('fund')->add($o_id, $user_id, $cashtype, $n, $amount);
				$this->db('order')->update($o_id, $fund_id);
				//TODO，根据子订单类型引用不同的STATUS_PASS常量
				$this->db('order_'.$sub)->update($o_id, self::STATUS_PASS);

			}

		//订单、资产相关DB操作遇到错误均会抛异常，直接捕获，model db对象注销时自动rollback
		}catch(\Exception $e){
			writeLog('exception', 'dal_order', $e->getMessage());
			$this->db()->rollback();
			return false;
		}

		$this->db()->commit();
		//标记自动打款
		if($status == self::STATUS_PASS)
			$this->redis('queue')->addAutopayJob($cashtype, D('myuser')->getId());

		return $o_id;
	}

	/**
	 * 封装增加红包订单便捷方法
	 * @param bigint $user_id  用户ID
	 * @param int    $gifttype 新人礼包类型，常量定义见开篇
	 * @param int    $amount   红包价值金额单位分，仅当类型为新人抽奖/新人任务时有效，且不能超过100
	 * return array            o_id, amount
	 */
	function addCashgift($user_id, $gifttype, $amount=0){

		if($amount>100)return; //保护金额

		if(array_search($gifttype, array(self::CASHGIFT_GIFTTYPE_LUCK, self::CASHGIFT_GIFTTYPE_TASK, self::CASHGIFT_GIFTTYPE_COND_10, self::CASHGIFT_GIFTTYPE_COND_20, self::CASHGIFT_GIFTTYPE_COND_50, self::CASHGIFT_GIFTTYPE_COND_100))===false)return; //保护类型

		$status = self::STATUS_WAIT_CONFIRM;

		switch ($gifttype) {
			case self::CASHGIFT_GIFTTYPE_COND_10:
				$amount = 1000;
				$cashtype = self::CASHTYPE_CASH;
				break;
			case self::CASHGIFT_GIFTTYPE_COND_20:
				$amount = 2000;
				$cashtype = self::CASHTYPE_CASH;
				break;
			case self::CASHGIFT_GIFTTYPE_COND_50:
				$amount = 5000;
				$cashtype = self::CASHTYPE_CASH;
				break;
			case self::CASHGIFT_GIFTTYPE_COND_100:
				$amount = 10000;
				$cashtype = self::CASHTYPE_CASH;
				break;
			case self::CASHGIFT_GIFTTYPE_LUCK:
			case self::CASHGIFT_GIFTTYPE_TASK:
				$cashtype = self::CASHTYPE_JFB;
				$status = self::STATUS_PASS;
				break;
		}

		//TODO 不允许重复增加新人礼包
		$ret = $this->add($user_id, $status, 'cashgift', $cashtype, self::N_ADD, $amount, array('gifttype'=>$gifttype));
		if($ret){
			$ret_true = array();
			$ret_true['amount'] = $amount;
			$ret_true['o_id'] = $ret;
		}
		return $ret;
	}


	function _renderStatus($list) {

		$map = C('options', 'order_status');
		foreach ($list as & $v) {
			if(isset($v['status']))$v['status_display'] = $map[$v['status']];
		}
		return $list;
	}
}
?>