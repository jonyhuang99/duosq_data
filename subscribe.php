<?php
//DAL:订阅信息管理
namespace DAL;

class Subscribe extends _Dal {

	//临时创建保存配置的会话
	function sessCreate($channel='email'){

		if(!$channel || !in_array($channel, array('email','mobile','ios','android','weixin')))return;

		$sess_id = date('Ymd_His_').rand(100000, 999999);
		if($this->redis('subscribe')->create($sess_id))
			return $sess_id;
		else
			return false;
	}

	//探测订阅会话是否存在
	function sessCheck($sess_id){

		if(!$sess_id)return;
		return $this->redis('subscribe')->check($sess_id);
	}

	//获取用户订阅信息
	function detail($account, $channel='email', $field=''){

		if(!$account)return false;
		$ret = $this->db('promotion.subscribe')->detail($account, $channel);

		if($ret && $field){
			return $ret[$field];
		}
		return $ret;
	}

	//读取订阅设置
	function getSetting($account, $channel='email'){

		if(!$account)return false;
		$ret = $this->detail($account, $channel);
		if(!$ret)return false;

		//清掉和配置无关的字段
		foreach($ret as $option => $val){
			if(stripos($option, 'setting')===false && $option != 'status'){
				unset($ret[$option]);
			}
		}

		if($ret['setting_brand']){
			$ret['setting_brand'] = explode(',', $ret['setting_brand']);
			$i = 0;
			foreach($ret['setting_brand'] as $brand_id){
				if(!D('brand')->detail($brand_id))
					unset($ret['setting_brand'][$i]);
				$i++;
			}
		}else{
			$ret['setting_brand'] = array();
		}

		if($ret['setting_subcat']){
			$ret['setting_subcat'] = explode(',', $ret['setting_subcat']);
		}else{
			$ret['setting_subcat'] = array();
		}

		if($ret['setting_midcat']){
			$ret['setting_midcat'] = explode(',', $ret['setting_midcat']);
		}else{
			$ret['setting_midcat'] = array();
		}

		if($ret['setting_clothes_color']){
			$ret['setting_clothes_color'] = explode(',', $ret['setting_clothes_color']);
		}else{
			$ret['setting_clothes_color'] = array();
		}

		return $ret;
	}

	//初始化指定会话的全部配置
	function sessInit($sess_id, $setting=array()){

		if(!$this->sessCheck($sess_id))return false;

		if(isset($setting['setting_brand']) && $setting['setting_brand']){
			$setting['setting_brand'] = join(',', $setting['setting_brand']);
		}else{
			$setting['setting_brand'] = '';
		}

		if(isset($setting['setting_subcat']) && $setting['setting_subcat']){
			$setting['setting_subcat'] = join(',', $setting['setting_subcat']);
		}else{
			$setting['setting_subcat'] = '';
		}

		if(isset($setting['setting_midcat']) && $setting['setting_midcat']){
			$setting['setting_midcat'] = join(',', $setting['setting_midcat']);
		}else{
			$setting['setting_midcat'] = '';
		}

		if(isset($setting['setting_clothes_color']) && $setting['setting_clothes_color']){
			$setting['setting_clothes_color'] = join(',', $setting['setting_clothes_color']);
		}else{
			$setting['setting_clothes_color'] = '';
		}

		foreach($setting as $key => $value){

			$this->redis('subscribe')->set($sess_id, $key, $value);
		}
		return true;
	}

	//从订阅会话中，更改指定option的值，或删除指定option
	function sessUpdate($sess_id, $option, $value=null, $action='add'){

		if(!$this->sessCheck($sess_id) || !$option)return false;
		//加锁防止并发save导致脏数据
		$l = 0;
		while(!$this->redis('lock')->getlock(\Redis\Lock::LOCK_SUBSCRIBE_OPTION, $sess_id)){
			usleep(1000);
			if($l > 5000)break;
			$l++;
		}

		switch ($option) {
			case 'setting_subcat':
			case 'setting_midcat':
			case 'setting_brand':
			case 'setting_clothes_color':

				if($option == 'setting_brand' || $option == 'setting_clothes_color'){
					$value = intval($value);
					if(!$value)return false;
				}

				if($option == 'setting_subcat'){
					if(!D('promotion')->subcat2cat($value))return false;
				}

				if($option == 'setting_midcat'){
					if(!D('promotion')->midcat2cat($value))return false;
				}

				$sess_setting_str = $this->redis('subscribe')->get($sess_id, $option);
				$sess_setting = array();
				if($sess_setting_str){
					$tmp = explode(',', $sess_setting_str);
					foreach($tmp as $s){
						$sess_setting[$s] = 1;
					}
				}else{
					$sess_setting = array();
				}
				if($action == 'add'){
					$sess_setting[$value] = 1;
				}else{
					unset($sess_setting[$value]);
				}
				$sess_setting = join(',', array_keys($sess_setting));
				break;

			case 'setting_clothes_size_girl':
			case 'setting_clothes_size_boy':

				if(!in_array($value, array('s','m','l','xl','xxl')))return false;
				if($action == 'add'){
					$sess_setting = $value;
				}else{
					$sess_setting = '';
				}
				break;

			case 'setting_shoes_size_girl':
			case 'setting_shoes_size_boy':

				$value = intval($value);
				if(!$value)return false;
				if($action == 'add'){
					$sess_setting = $value;
				}else{
					$sess_setting = '';
				}
				break;
		}

		$ret = $this->redis('subscribe')->set($sess_id, $option, $sess_setting);
		$this->redis('lock')->unlock(\Redis\Lock::LOCK_SUBSCRIBE_OPTION, $sess_id);
		return $ret;
	}

	//从订阅会话保存配置到数据库
	function sessSave($sess_id, $account, $channel='email'){

		if(!$sess_id || !$account)return;

		//可能没有配置
		$setting = $this->redis('subscribe')->get($sess_id);
		if(!$setting)$setting = array();
		$exist = $this->db('promotion.subscribe')->detail($account, $channel);

		$setting['updatetime'] = date('Y-m-d H:i:s');
		$setting['status'] = \DB\Subscribe::STATUS_NORMAL;
		if(isset($_GET['push_token']))$setting['push_token'] = $_GET['push_token'];

		if($exist){
			//用空来覆盖旧配置
			foreach ($exist as $key => $value) {
				if(strpos($key, 'setting')!==false){
					if(!isset($setting[$key]) || !$setting[$key]){
						$setting[$key] = '';
					}
				}
			}

			$ret = $this->db('promotion.subscribe')->update($account, $channel, $setting);
		}else{
			$ret = $this->db('promotion.subscribe')->add($account, $channel, $setting);
		}

		if($ret){
			$this->redis('subscribe')->clean($sess_id);
			return true;
		}
	}

	//退订订阅(允许首次就是退订状态)
	function refuse($account, $channel='email'){

		if(!$this->db('promotion.subscribe')->detail($account)){
			$ret = $this->db('promotion.subscribe')->add($account, $channel, array('status'=>\DB\Subscribe::STATUS_STOP, 'updatetime'=>date('Y-m-d H:i:s')));
		}else{
			$this->db('promotion.subscribe')->update($account, $channel, array('status'=>\DB\Subscribe::STATUS_STOP, 'updatetime'=>date('Y-m-d H:i:s')));
			$ret = true;
		}
		return $ret;
	}

	//标识已经接收到了通知，$message_ids为批量时，times_open也只累加1次
	function markMessageOpened($account, $channel, $message_ids){

		if(!$account || !$channel || !$message_ids)return false;
		$ids = explode(',', $message_ids);
		foreach($ids as $id){
			$detail = $this->db('promotion.subscribe_message')->detail($account, $channel, $id);
			if($detail['status'] != \DB\SubscribeMessage::STATUS_OPENED){
				$this->db('promotion.subscribe_message')->update($account, $channel, $id, array('status'=>\DB\SubscribeMessage::STATUS_OPENED, 'opentime'=>date('Y-m-d H:i:s')));
			}
		}

		$times_open = $this->db('promotion.subscribe')->detail($account, $channel, 'times_open');
		$times_open += 1;
		$this->db('promotion.subscribe')->update($account, $channel, array('times_open'=>$times_open));

		//标识用户打开了信息，进入队列计算notify_num推送更新后的消息数
		$this->sendAppOpenMsg($account, $channel);
		return true;
	}

	//标识消息已经推送
	function markMessagePushed($account, $channel, $message_id, $succ=true){

		if(!$account || !$channel || !$message_id)return;

		$detail = $this->db('promotion.subscribe')->detail($account, $channel);

		if($succ){
			$times_push_succ = $detail['times_push_succ'] + 1;
			$this->db('promotion.subscribe')->update($account, $channel, array('times_push_succ'=>$times_push_succ, 'pushtime'=>date('Y-m-d H:i:s')));
			$this->db('promotion.subscribe_message')->update($account, $channel, $message_id, array('status'=>\DB\SubscribeMessage::STATUS_SUCC, 'pushtime'=>date('Y-m-d H:i:s')));
		}else{
			$times_push_fail = $detail['times_push_fail'] + 1;
			$this->db('promotion.subscribe')->update($account, $channel, array('times_push_fail'=>$times_push_fail, 'pushtime'=>date('Y-m-d H:i:s')));
			$this->db('promotion.subscribe_message')->update($account, $channel, $message_id, array('status'=>\DB\SubscribeMessage::STATUS_FAIL, 'pushtime'=>date('Y-m-d H:i:s')));
		}
	}

	//获取待推送消息
	function getWaitPushMessageList($channel='email', $limit=100){

		return $this->db('promotion.subscribe_message')->getList('','', array('status'=>\DB\SubscribeMessage::STATUS_WAIT, 'channel'=>$channel), $limit);
	}

	//读取订阅消息列表
	function getMessageList($account, $channel, $cond=array(), $limit=10){

		if(!$account || !$channel)return false;
		return $this->db('promotion.subscribe_message')->getList($account, $channel, $cond, $limit);
	}

	//获取订阅消息详情
	function getMessageDetail($account, $channel, $message_id){

		if(!$account || !$channel || !$message_id)return;
		return $this->db('promotion.subscribe_message')->detail($account, $channel, $message_id);
	}

	//读取未打开消息数
	function getUnOpenedMessageCount($device_id='', $platform=''){

		if(!$device_id || !$platform)return 0;
		$this->db('promotion.subscribe_message');
		$lines = $this->getMessageList($device_id, $platform, array('status'=>'< '.\DB\SubscribeMessage::STATUS_OPENED), 99);
		if(!$lines)return 0;
		return count($lines);
	}

	//获取用户已被发送过的特卖(2个月内)
	function getMemberPushedPromo($account, $channel, $limit_month=2){

		if(!$account || !$channel)return false;

		$key = 'subscribe:pushed_promo:channel:'.$channel.':account:'.$account.':limit_month:'.$limit_month;
		$cache = D('cache')->get($key);
		if($cache)return D('cache')->ret($cache);

		$lists = $this->getMessageList($account, $channel, $cond=array('createtime' => '> '.date('Y-m-d', time()-MONTH*$limit_month)));

		$promo = array();
		if($lists){
			foreach ($lists as $list) {
				$message = unserialize($list['message']);
				foreach ($message as $p) {
					$promo[$p['sp'].'_'.$p['goods_id']] = $p;
				}
			}
		}

		D('cache')->set($key, $promo, DAY*C('comm', 'subscribe_push_space'), true);
		return $promo;
	}

	//获取今日等待推送特卖的用户数
	function getNeedPushMemberNum(){

		return D('subscribe')->db('promotion.subscribe')->findCount(array('status'=>\DB\Subscribe::STATUS_NORMAL, 'pushtime'=>'<= '.date('Y-m-d', strtotime(date('Y-m-d'))-DAY*(C('comm', 'subscribe_push_space')-1))));
	}

	//清空推送候选数据
	function initPushCandidate(){

		$this->db('promotion.subscribe_cand_push')->query("truncate table duosq_promotion.subscribe_cand_push");
		$this->db('promotion.subscribe_cand')->query("UPDATE duosq_promotion.subscribe_cand SET mark = 0");
		$this->db('promotion.subscribe_cand')->query("DELETE FROM duosq_promotion.subscribe_cand WHERE createdate < '".date('Y-m-d', time()-MONTH*2)."'");
		return true;
	}

	//获取待推送的候选特卖
	function getCandidatePromo(){

		$this->db('promotion.subscribe_cand');
		$promo_candidator = $this->db('promotion.subscribe_cand')->findAll(array('status'=>\DB\SubscribeCand::STATUS_NORMAL, 'createdate'=>'>= '.date('Y-m-d', time()-DAY*C('comm', 'subscribe_push_space'))));
		return clearTableName($promo_candidator);
	}

	//获取待推送会员
	function getWaitPushCandidateMembers($limit=1000, $page=1){


		$ret = $this->db('promotion.subscribe')->findAll(array('status'=>\DB\Subscribe::STATUS_NORMAL, 'pushtime'=>'<= '.date('Y-m-d', strtotime(date('Y-m-d'))-DAY*(C('comm', 'subscribe_push_space')-1))), 'channel,account', '', $limit, $page);
		return clearTableName($ret);
	}

	//更新候选特卖
	function updatePromoCand($sp, $goods_id, $data){

		if(!$sp ||!$goods_id ||!$data)return;
		return D('promotion')->db('promotion.subscribe_cand')->update($sp, $goods_id, $data);
	}

	//标识特卖为待推送状态
	function markCandidatePromoWaitPush($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$this->db('promotion.subscribe_cand')->update($sp, $goods_id, array('mark'=>1));
	}

	//获取指定会员待推送候选特卖
	function getPushCandidatePromo($account, $channel='email', $limit=50){

		$ret = $this->db('promotion.subscribe_cand_push')->findAll(array('account'=>$account, 'channel'=>$channel), '', 'hit_weight DESC, promo_weight DESC', $limit);
		return clearTableName($ret);
	}

	//清除指定用户的待推送候选特卖
	function deletePushCandidatePromoByAccount($account, $channel='email'){

		if(!$account || !$channel)return;
		$this->db('promotion.subscribe_cand_push')->query("DELETE FROM duosq_promotion.subscribe_cand_push WHERE channel='{$channel}' AND account='{$account}'");
	}

	//清除指定指定的待推送候选特卖
	function deletePushCandidatePromoByGoodsID($sp, $goods_id){

		if(!$sp || !$goods_id)return;
		$this->db('promotion.subscribe_cand_push')->query("DELETE FROM duosq_promotion.subscribe_cand_push WHERE sp = '{$sp}' AND goods_id = '{$goods_id}'");
	}

	//清除指定指定的待推送候选特卖
	function deletePushCandidatePromoNotInGoodsID($account, $channel='email', $goods_ids){

		if(!$account || !$channel || !$goods_ids)return;
		$this->db('promotion.subscribe_cand_push')->query("DELETE FROM duosq_promotion.subscribe_cand_push WHERE channel='{$channel}' AND account='{$account}' AND goods_id NOT IN(".join(',', $goods_ids).")");
	}

	/**
	 * 发送用户打开了推送的消息
	 */
	function sendAppOpenMsg($account, $channel='email'){

		if(!$account || !$channel)return;
		if(!in_array($channel, array('ios', 'android')))return;
		return D()->redis('queue')->add(\REDIS\Queue::KEY_APP_NOTIFY_NUM, $channel.'::'.$account);
	}

	/**
	 * 获取用户打开了推送的消息
	 */
	function getAppOpenMsg(){

		$msg = D()->redis('queue')->bget(\REDIS\Queue::KEY_APP_NOTIFY_NUM);
		if($msg){
			list($channel, $account) = explode('::', $msg);
			return array('channel'=>$channel, 'account'=>$account);
		}
		return false;
	}

	/**
	 * 完成打开了推送的消息
	 * @param  int    $fund_id    资产流水ID
	 * @return bool               是否执行成功
	 */
	function doneAppOpenMsg($account, $channel = 'email'){

		if(!$account || !$channel)return;
		return D()->redis('queue')->done(\REDIS\Queue::KEY_APP_NOTIFY_NUM, $channel.'::'.$account);
	}

	//获取最后APP推送标识的未读消息数
	function getAppLastNotifyNum($account, $channel='email'){
		return D()->redis('keys')->appLastNotifyNum($account, $channel);
	}

	//设置APP推送标识的未读消息数
	function setAppLastNotifyNum($account, $channel='email', $num=0){
		return D()->redis('keys')->appLastNotifyNum($account, $channel, $num);
	}
}
?>