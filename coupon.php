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

		if($this->lock($user_id)){
			$ret = $this->hasChance($user_id, $type, $error);
			if(!$ret){
				$this->unlockNum();
				$err = $error;
				$this->unlock($user_id);
				return false;
			}

			if(!$this->lock_id){
				//没能抢到锁，但作为特例允许，抢到的数额不计入今日数额，这将导致可能新人今日可以抢2张
				$ret = $this->db('coupon')->save(array('type'=>$type, 'user_id'=>$user_id, 'createdate'=>date('Y-m-d', time()-DAY), 'expiredate'=>date('Y-m-d', time()+DAY*30)));
			}else{
				$ret = $this->db('coupon')->save(array('type'=>$type, 'user_id'=>$user_id, 'expiredate'=>date('Y-m-d', time()+DAY*30)));
			}
			$this->unlock($user_id);
			if(!$ret){
				$err = '系统生成优惠券失败，请重试!';
				return false;
			}else{
				return true;
			}
		}else{

			$err = '您的请求已在处理中，请稍等10秒钟!';
			$this->unlock($user_id);
			return false;
		}
	}

	//使用优惠券
	function active($type, $o_id, $user_id){

		if(!$type || !$o_id || !$user_id)return;

		if($type == self::TYPE_DOUBLE){
			$in_type = self::TYPE_DOUBLE;
		}else if($type == 'free'){
			$in_type = array(self::TYPE_FREE_50, self::TYPE_FREE_100, self::TYPE_FREE_200, self::TYPE_FREE_300);
		}

		//TODO 此处需加业务锁
		$coupon_id = $this->db('coupon')->field('id', array('is_used'=>0, 'user_id'=>$user_id, 'type'=>$in_type, 'expiredate'=>'>= '.date('Y-m-d')));

		if($coupon_id){
			$detail = clearTableName($detail);
			//判断订单归属
			if(D('order')->detail($o_id, 'user_id')!= $user_id)return;

			//判断该订单是否已使用了优惠券
			if($this->isUsed($o_id))return;
			return $this->db('coupon')->save(array('id'=>$coupon_id, 'o_id'=>$o_id, 'is_used'=>1));
		}
	}

	//判断订单是否使用了优惠券
	function isUsed($o_id, $field='type'){
		if(!$o_id)return false;

		if($field != 'detail'){
			return $this->db('coupon')->field($field, array('o_id'=>$o_id));
		}else{
			$ret = $this->db('coupon')->find(array('o_id'=>$o_id));
			return clearTableName($ret);
		}
	}

	//读取用户的有效优惠券
	function getSummary($user_id){

		if(!$user_id)return array();
		$coupons = $this->db('coupon')->findAll(array('user_id'=>$user_id, 'is_used' => 0, 'expiredate' => '>= '.date('Y-m-d')));

		$ret = array();
		if($coupons){
			$coupons = clearTableName($coupons);
			foreach($coupons as $coupon){
				@$ret[$coupon['type']] += 1;
			}
		}
		return $ret;
	}

	//获取当天该类优惠券数量
	function getLeft($t=''){

		$all_type = array_keys($this->limit_num);
		$left = array();

		if($t){
			$all_type = array($t);
		}
		foreach($all_type as $type){
			$today_limit = intval($this->limit_num[$type]);
			$count = $this->db('coupon')->findCount(array('createdate'=>date('Y-m-d'), 'type'=>$type));
			if($type == self::TYPE_DOUBLE){
				$left[$type] =  $today_limit*15-$count;
			}else{
				$left[$type] =  $today_limit-$count;
			}

			//补充第一次抽奖人看到的数量
			if($type == self::TYPE_DOUBLE && $left[$type] < 1 && $this->firstTime()){
				$left[$type] = 1;
			}
		}

		if($t){
			return $left[$t];
		}

		return $left;
	}

	//获取当天领了优惠券的幸运儿
	function getLuckUsers(){

		$luck_users = $this->db('coupon')->findAll(array('createdate'=>date('Y-m-d'), 'type'=>self::TYPE_DOUBLE), '', 'id DESC', 10);
		$list = array();
		if($luck_users){
			$luck_users = clearTableName($luck_users);
			foreach($luck_users as $detail){
				$list[$detail['type']][$detail['user_id']] = $detail;
			}
		}

		$luck_users2 = $this->db('coupon')->findAll(array('createdate'=>'>'.date('Y-m-d', time()-DAY*10), 'type'=>'<> '.self::TYPE_DOUBLE), '', 'id DESC', 10);
		if($luck_users2){
			$luck_users2 = clearTableName($luck_users2);
			foreach($luck_users2 as $detail){
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
		if(!$this->lockNum($type)){
			$err = '已被抢光，下轮开抢时间：'.date('H', time()+HOUR).':00点整，每小时开放'.$this->limit_num[$type].'张!';
			return false;
		}

		//判断当天还有余量
		if($this->getLeft($type) < 1){
			$err = '太火爆了，该券今天已被抢光，请明天再来！';
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
				$err = '您今天已经抢过该类券，'.$limit_cyc.'天内不能再抢了！';
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

	//判断该用户是否首次抢券
	private function firstTime(){

		if(!D('myuser')->isLogined())return false;
		$hit = $this->db('coupon')->find(array('user_id'=>D('myuser')->getId()));
		if(!$hit)return true;
	}

	//抢券锁库存
	private function lockNum($type){

		$H = date('H');

		for($i=1; $i < $this->limit_num[$type]+1; $i++){

			$lock_id = "{$H}_type_{$type}_num_".$i;
			$succ = $this->redis('lock')->getlock(\Redis\Lock::LOCK_COUPON_ROB_NUM, $lock_id);
			if($succ){
				$this->lock_id = $lock_id;
				return true;
			}
		}

		//首次必须获得库存
		if($this->firstTime() && $type == self::TYPE_DOUBLE){
			return true;
		}

		return false;
	}

	//抢券失败解锁库存
	private function unlockNum(){

		if($this->lock_id)
			$this->redis('lock')->unlock(\Redis\Lock::LOCK_COUPON_ROB_NUM, $this->lock_id);
	}

	//抢券锁业务
	private function lock($user_id){

		$ret = $this->redis('lock')->getlock(\Redis\Lock::LOCK_COUPON_ROB, $user_id);
		return $ret;
	}

	//抢券业务解锁
	private function unlock($user_id){

		$this->redis('lock')->unlock(\Redis\Lock::LOCK_COUPON_ROB, $user_id);
	}
}
?>