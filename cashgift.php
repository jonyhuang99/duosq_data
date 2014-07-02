<?php
//DAL:现金红包逻辑模块
namespace DAL;

class Cashgift extends _Dal {

	/**
	 * 渲染当前购物省钱，条件红包激活金额，还差金额，返回红包激活说明
	 * @param  int    $gifttype 红包类型
	 * @return [type]           [description]
	 */
	function getTip($gifttype){

		switch ($gifttype) {
			case \DB\OrderCashgift::GIFTTYPE_COND_10:
				$tip = C('tags', 'tip_cashgift_10');
				break;
			case \DB\OrderCashgift::GIFTTYPE_COND_20:
				$tip = C('tags', 'tip_cashgift_20');
				break;
			case \DB\OrderCashgift::GIFTTYPE_COND_50:
				$tip = C('tags', 'tip_cashgift_50');
				break;
			case \DB\OrderCashgift::GIFTTYPE_COND_100:
				$tip = C('tags', 'tip_cashgift_100');
				break;
			default:
				return false;
				break;
		}

		$user_id = D('myuser')->getId();

		$current = D('fund')->getShoppingBalance($user_id);

		$gift = D('order')->getSubList('cashgift', array('user_id'=>$user_id, 'gifttype'=>$gifttype, 'status'=>\DB\OrderCashgift::STATUS_WAIT_ACTIVE), 'reach ASC', 1);
		if($gift){
			$gift = clearTableName($gift);
			$reach = $gift['reach'];
		}else{
			return false;
		}

		$tip = str_replace('{current}', price($current), $tip);
		$tip = str_replace('{reach}', price($reach), $tip);
		$tip = str_replace('{left}', price($reach - $current), $tip);
		return $tip;
	}

	/**
	 * 获取指定用户现金红包总和信息
	 * @param  int    $user_id 用户ID
	 * @param  int    $status  红包状态
	 * @return array           用户红包总和
	 */
	function getSummary($user_id, $status=''){

		if(!$user_id)return false;
		//1-新人抽奖 2-新人任务 5-新人条件红包
		$sent = $this->getList($user_id, $status);
		$gifttype = array();
		if($sent){
			foreach($sent as $s){
				@$gifttype[$s['gifttype']] += 1;
			}
		}

		return $gifttype;
	}

	/**
	 * 获取指定用户所有红包
	 * @param  int    $user_id 用户ID
	 * @param  int    $status  红包状态
	 * @return array           用户红包列表
	 */
	function getList($user_id, $status=''){

		if(!$user_id)return false;
		$gifts = D('order')->getSubList('cashgift', array('user_id'=>$user_id, 'status'=>$status));
		return $gifts;
	}
}
?>