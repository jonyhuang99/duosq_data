<?php
//DAL:速控限制模块
namespace DAL;

class Speed extends _Dal {

	/**
	 * 发送邮件速度控制
	 * @param  [type] $emails 待发目标EMAIL，目前支持1个
	 * @return [type]         [description]
	 */
	function email($emails){

		if(!$emails)return false;

		$rule = array(
			'qq.com'=>array('second'=>1,'minute'=>20,'hour'=>500),
			'163.com'=>array('second'=>2,'minute'=>20,'hour'=>500),
			'126.com'=>array('second'=>2,'minute'=>20,'hour'=>500),
			'gmail.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'hotmail.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'live.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'yahoo.com.cn'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'yahoo.cn'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'sina.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'sohu.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'139.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'yeah.net'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'other'=>array('second'=>4,'minute'=>200,'hour'=>12000)
		);

		foreach($emails as $email){

			$ext = low(array_pop(explode('@', $email)));

			if(isset($rule[$ext])){
				$limit = $rule[$ext];
			}else{
				$limit = $rule['other'];
			}

			$this->redis('speed')->isSafe('email:'.$ext, 1, $limit['second']);
			$this->redis('speed')->isSafe('email:'.$ext, 60, $limit['minute']);
			$this->redis('speed')->isSafe('email:'.$ext, 3600, $limit['hour']);
		}

		return true;
	}

	/**
	 * 注册用户名命中黑名单
	 * @return [type] [description]
	 */
	function blacklist($mode = 'set'){

		if($mode == 'set'){
			return $this->redis('speed')->sincr('send_cashgift:black_list:ip:'.getIpByLevel('c'), DAY, 1);
		}else{
			return $this->redis('speed')->sget('send_cashgift:black_list:ip:'.$ip_c, DAY, 1);
		}
	}

	/**
	 * 控制新人获取礼包速度，是否超出
	 * @param  [type] $limit_type [description]
	 * @param  [type] $mobile     [description]
	 * @return [type]             [description]
	 */
	function cashgift($limit_type='ip', $phrase=''){

		if($limit_type == 'ip'){
			$limit = 4;
			return $this->redis('speed')->sincr('send_cashgift:ip_b:'.getIpByLevel('b'), HOUR*6, $limit);
		}

		if($limit_type == 'mobile'){
			if(!$phrase)return false;
			$phrase_pre = substr($phrase, 0, 7);
			$limit = 3;
			$ret = $this->redis('speed')->sincr('send_cashgift:mobile_pre:'.$phrase_pre, HOUR*24, $limit);
			if($ret){
				return $phrase_pre;
			}
		}

		if($limit_type == 'alipay_pre'){
			if(!$phrase)return false;
			$phrase_pre = substr($phrase, 0, 4);
			$limit = 5;
			$ret = $this->redis('speed')->sincr('send_cashgift:alipay_pre:'.$phrase_pre, HOUR*12, $limit);
			if($ret){
				return $phrase_pre;
			}
		}

		if($limit_type == 'agent'){
			$agent = getAgent();
			if(stripos($agent, '21.0.1180.89')!==false){
				return false;
			}

			if(stripos($agent, '30.0.1599')!==false){
				return false;
			}

			$area_detail = getAreaByIp('', 'detail');
			$limit = 4;
			$ret = $this->redis('speed')->sincr('send_cashgift:area:'.$area_detail.':agent:'.md5($agent), HOUR*4, $limit);
			if($ret){
				return array('area_detail'=>$area_detail, 'agent'=>$agent);
			}

			$limit = 20;
			$ret = $this->redis('speed')->sincr('send_cashgift:agent:'.md5($agent), HOUR, $limit);
			if($ret){
				return array('agent'=>$agent);
			}
		}

		if($limit_type == 'country'){

			$area_detail = getAreaByIp('', 'detail');
			$ret = getProvince($area_detail);
			if(!$ret){
				return $area_detail;
			}
		}
	}

	/**
	 * 用户自助跟单速控
	 * @return [bool] [是否超速]
	 */
	function chUser($mode = 'set'){

		if($mode == 'set'){
			return $this->redis('speed')->sincr('ch_user:user_id:'.D('myuser')->getId(), DAY, 4);
		}else{
			return $this->redis('speed')->sget('ch_user:user_id:'.D('myuser')->getId(), DAY, 4);
		}
	}

	/**
	 * 加好友申请速度
	 * @return [bool] [是否超速]
	 */
	function friendAsk(){

		return $this->redis('speed')->sincr('friend_ask:user_id:'.D('myuser')->getId().':date:'.date('Y-m-d'), HOUR*24, C('comm', 'friend_ask_limit'));
	}

	/**
	 * 红包产生通知，每人每周仅发一次
	 * @return [bool] [是否超速]
	 */
	function notifiedCashgiftReward($user_id){

		return $this->redis('speed')->sincr('cashgift_reward_notify:user_id:'.$user_id.':week:'.date('W'), DAY*7, 1);
	}

	/**
	 * 到账通知，每人每天通知一次
	 * @return [bool] [是否超速]
	 */
	function notifiedPaymentComplete($user_id){

		return $this->redis('speed')->sincr('payment_complete_notify:user_id:'.$user_id.':day:'.date('d'), DAY, 1);
	}

	/**
	 * 订单跟单成功通知，每天通知一次
	 * @return [bool] [是否超速]
	 */
	function notifiedOrderBack($user_id){

		return $this->redis('speed')->sincr('order_back_notify:user_id:'.$user_id.':day:'.date('d'), DAY, 1);
	}

	/**
	 * 集分宝支付，每天不超过3次
	 * @return [bool] [是否超速]
	 */
	function payJfb($user_id, $mode='set'){

		if($check == 'set'){
			return $this->redis('speed')->sincr('pay_jfb:user_id:'.$user_id.':day:'.date('d'), DAY, 2);
		}else{
			return $this->redis('speed')->sget('pay_jfb:user_id:'.$user_id.':day:'.date('d'), DAY, 2);
		}
	}

	/**
	 * 订阅邮件，C段IP，每小时不超过5次
	 * @return [bool] [是否超速]
	 */
	function subscribe(){

		$limit = $this->redis('speed')->sincr('subscribe:ip:'.getIp(), MINUTE*10, 20);
		if($limit){
			D('alarm')->subscribe(getIp());
			return true;
		}
	}

	/**
	 * 每日新增文章不超过15篇
	 * @return [bool] [是否超速]
	 */
	function importNews(){

		return $this->redis('speed')->sincr('import_news:date'.date('Y-m-d'), DAY*5, 5);
	}
}
?>