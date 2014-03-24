<?php
//DAL:优惠券模块
namespace DAL;

class Coupon extends _Dal {

	const TYPE_DOUBLE = 1;
	const TYPE_FREE_50 = 2;
	const TYPE_FREE_100 = 3;
	const TYPE_FREE_200 = 4;
	const TYPE_FREE_300 = 5;

	const START = 10; //开抢时间

	var $limit_num = array(1=>5, 2=>1, 3=>1, 4=>1, 5=>1); //券小时限额
	var $limit_level = array(1=>1, 2=>2, 3=>3, 4=>4, 5=>5); //券的等级要求
	var $lock_id = false;

	//判断今天开抢是否结束
	function end(){

		if(date('H') < self::START){
			return self::START;
		}else{
			return false;
		}
	}

	//抢优惠券
	function rob($user_id, $type, &$err){

		if(!$user_id || !$type){
			$err = '传参错误，请刷新页面重试！';
			return false;
		}

		$error = '';

		$ret = $this->hasChance($user_id, $type, $error);
		if(!$ret){
			$this->unlock();
			$err = $error;
			return false;
		}


		$ret = $this->db('coupon')->save(array('type'=>$type, 'user_id'=>$user_id, 'expiredate'=>date('Y-m-d', time()+DAY*30)));

		if(!$ret){
			$err = '系统生成优惠券失败，请重试!';
			return false;
		}else{
			return true;
		}
	}

	//使用优惠券
	function active($type, $o_id, $user_id){

		if(!$type || !$o_id || !$user_id)return;

		if($type == self::TYPE_DOUBLE){
			$in_type = self::TYPE_DOUBLE;
		}else{
			$in_type = array(self::TYPE_FREE_50, self::TYPE_FREE_100, self::TYPE_FREE_200, self::TYPE_FREE_300);
		}

		//TODO 此处需加业务锁
		$coupon_id = $this->db('coupon')->field('id', array('is_used'=>0, 'user_id'=>$user_id, 'type'=>$in_type, 'expiredate'=>'>= '.date('Y-m-d')));

		if($coupon_id){
			clearTableName($detail);
			if(D('order')->detail($o_id, 'user_id')!= $user_id)return;

			return $this->db('coupon')->save(array('id'=>$coupon_id, 'o_id'=>$o_id, 'is_used'=>1));
		}
	}

	//判断订单是否使用了优惠券
	function isMe($o_id){
		if(!$o_id)return false;
		return $this->db('coupon')->field('type', array('o_id'=>$o_id));
	}

	//读取用户的有效优惠券
	function getSummary($user_id){

		if(!$user_id)return array();
		$coupons = $this->db('coupon')->findAll(array('user_id'=>$user_id, 'is_used' => 0, 'expiredate' => '>= '.date('Y-m-d')));

		$ret = array();
		if($coupons){
			clearTableName($coupons);
			foreach($coupons as $coupon){
				@$ret[$coupon['type']] += 1;
			}
		}
		return $ret;
	}

	//获取当天该类优惠券数量
	function getLeft(){

		$all_type = array_keys($this->limit_num);
		$left = array();
		foreach($all_type as $type){
			$today_limit = intval($this->limit_num[$type]);
			$count = $this->db('coupon')->findCount(array('createdate'=>date('Y-m-d'), 'type'=>$type));
			$left[$type] =  $today_limit*14-$count;
		}
		return $left;
	}

	//获取当天领了优惠券的幸运儿
	function getLuckUsers(){

		$luck_users = $this->db('coupon')->findAll(array('createdate'=>date('Y-m-d')), '', 'id DESC');
		$list = array();
		if($luck_users){
			clearTableName($luck_users);
			foreach($luck_users as $detail){
				$list[$detail['type']][] = $detail;
			}
		}
		return $list;
	}

	//判断用户是否有抢券资格
	private function hasChance($user_id, $type, &$err){

		if(!$user_id)return;

		//时间满足
		if(date('H')<self::START){
			$err = '还没开始哟，每天早上'.self::START.'点开始，每个整点开抢！';
			return false;
		}

		//当前优惠券有余量
		if(!$this->lock($type)){
			$err = '已被抢光，下轮开抢时间：'.date('H', time()+HOUR).':00点整，每小时开放'.$this->limit_num[$type].'张!';
			return false;
		}

		//判断用户等级
		$level = D('user')->detail($user_id, 'level');
		if($level < $this->limit_level[$type]){
			$err = '您当前等级'.$level.'，该券需要等级'.$this->limit_level[$type].'(包括)以上哟';
			return false;
		}

		if($type == self::TYPE_DOUBLE){
			$limit_cyc = 1;
			$valid_type = self::TYPE_DOUBLE;
		}else{
			$limit_cyc = 7;
			$valid_type = array(self::TYPE_FREE_50, self::TYPE_FREE_100, self::TYPE_FREE_200, self::TYPE_FREE_300);
		}

		//用户本周期没抽过该类券
		if($this->db('coupon')->find(array('user_id'=>$user_id, 'createdate' => '> '.date('Y-m-d', time()-DAY*$limit_cyc), 'type'=>$valid_type))){

			if($limit_cyc == 1){
				$err = '您今天已经抢过该券，'.$limit_cyc.'天内不能再抢了！';
			}else{
				$err = '您在'.($limit_cyc-1).'天前已经抢过该券，'.$limit_cyc.'天内不能再抢了！';
			}

			return false;
		}

		//用户当前不存在未使用的有效券
		if($this->db('coupon')->find(array('user_id'=>$user_id, 'is_used' => 0, 'type'=>$valid_type, 'expiredate' => '>= '.date('Y-m-d')))){
			$err = '您已有一张尚未使用，必须得先用掉才能抢新的！';
			return false;
		}

		return true;
	}

	//抢券加锁
	private function lock($type){

		$H = date('H');
		for($i=1; $i < $this->limit_num[$type]+1; $i++){

			$lock_id = "{$H}_type_{$type}_num_".$i;
			$succ = $this->redis('lock')->getlock(\Redis\Lock::LOCK_COUPON_ROB, $lock_id);
			if($succ){
				$this->lock_id = $lock_id;
				return true;
			}
		}

		return false;
	}

	//抢券失败解锁
	private function unlock(){

		if($this->lock_id)
			$this->redis('lock')->unlock(\Redis\Lock::LOCK_COUPON_ROB, $this->lock_id);
	}
}
?>