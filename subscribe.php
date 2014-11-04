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

		if(!$account || !$channel)return false;

		$ret = $this->db('promotion.subscribe')->detail($account, $channel);

		if($ret && $field){
			return $ret[$field];
		}
		return $ret;
	}

	//更新用户订阅信息
	function update($account, $channel, $data){

		if(!$account || !$channel || !$data)return;
		return $this->db('promotion.subscribe')->update($account, $channel, $data);
	}

	//读取订阅设置
	function getSetting($account, $channel='email'){

		if(!$account)return false;
		$ret = $this->detail($account, $channel);
		if(!$ret)return array();

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

		$arr_setting = array('setting_ablumcat', 'setting_subcat', 'setting_midcat', 'setting_clothes_color', 'setting_clothes_style_girl', 'setting_clothes_style_boy', 'setting_clothes_size_girl', 'setting_clothes_size_boy', 'setting_shoes_size_girl', 'setting_shoes_size_boy');

		foreach ($arr_setting as $s) {
			if($ret[$s]){
				$ret[$s] = explode(',', $ret[$s]);
			}else{
				$ret[$s] = array();
			}
		}

		return $ret;
	}

	//初始化指定会话的全部配置
	function sessInit($sess_id, $setting=array()){

		if(!$this->sessCheck($sess_id))return false;

		$arr_setting = array('setting_brand', 'setting_ablumcat', 'setting_subcat', 'setting_midcat', 'setting_clothes_color', 'setting_clothes_style_girl', 'setting_clothes_style_boy', 'setting_clothes_size_girl', 'setting_clothes_size_boy', 'setting_shoes_size_girl', 'setting_shoes_size_boy');

		foreach ($arr_setting as $s) {
			if(isset($setting[$s]) && $setting[$s]){
				$setting[$s] = join(',', $setting[$s]);
			}else{
				$setting[$s] = '';
			}
		}

		foreach($setting as $key => $value){

			$this->redis('subscribe')->set($sess_id, $key, $value);
		}
		return true;
	}

	//从订阅会话中，更改指定option的值，或删除指定option
	function sessUpdate($sess_id, $option, $value=null, $action='add', $self_call=false){

		if(!$this->sessCheck($sess_id) || !$option)return false;
		//加锁防止并发save导致脏数据
		$l = 0;
		while(!$this->redis('lock')->getlock(\Redis\Lock::LOCK_SUBSCRIBE_OPTION, $sess_id)){
			usleep(1000);
			if($l > 5000)break;
			$l++;
		}

		if($option == 'setting_brand' || $option == 'setting_clothes_color'){
			$value = intval($value);
			if(!$value)return false;
		}

		if($option == 'setting_ablumcat'){
			$ablumcat_option = C('options', 'subscribe_setting_ablumcat');
			if(!$ablumcat_option[$value])return false;
		}

		if($option == 'setting_subcat'){
			if(!D('promotion')->subcat2cat($value))return false;
		}

		if($option == 'setting_midcat'){
			if(!D('promotion')->midcat2cat($value))return false;
		}

		if($option == 'setting_clothes_size_girl' || $option == 'setting_clothes_size_boy' || $option == 'setting_shoes_size_girl' || $option == 'setting_shoes_size_boy'){
			$setting_option = C('options', 'subscribe_'.$option);
			if(!isset($setting_option[$value]))return false;
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

		$ret = $this->redis('subscribe')->set($sess_id, $option, $sess_setting);
		$this->redis('lock')->unlock(\Redis\Lock::LOCK_SUBSCRIBE_OPTION, $sess_id);

		//setting_subcat && setting_midcat 条件互斥
		if($ret && !$self_call){

			if($option == 'setting_subcat'){
				$midcat = D('promotion')->subcat2midcat($value);
				$this->sessUpdate($sess_id, 'setting_midcat', $midcat, 'del', true);
			}

			if($option == 'setting_midcat'){
				$subcats = D('promotion')->midcat2subcat($value);
				foreach ($subcats as $subcat) {
					$this->sessUpdate($sess_id, 'setting_subcat', $subcat, 'del', true);
				}
			}
		}

		return $ret;
	}

	//从订阅会话保存配置到数据库
	function sessSave($sess_id, $account, $channel='email'){

		if(!$sess_id || !$account || !$channel)return;

		//可能没有配置
		$setting = $this->redis('subscribe')->get($sess_id);
		if(!$setting)$setting = array();
		$exist = $this->detail($account, $channel);

		$setting['updatetime'] = date('Y-m-d H:i:s');
		$setting['status'] = \DB\Subscribe::STATUS_NORMAL;
		//每次都同步新的push_token
		if(isset($_GET['push_token']))$setting['push_token'] = $_GET['push_token'];

		if($exist){
			//用空来覆盖旧配置，用于删除配置
			foreach ($exist as $key => $value) {
				if(strpos($key, 'setting')!==false){
					if(!isset($setting[$key]) || !$setting[$key]){
						$setting[$key] = '';
					}
				}
			}

			$ret = $this->update($account, $channel, $setting);
		}else{
			$ret = $this->db('promotion.subscribe')->add($account, $channel, $setting);
		}

		if($ret){
			$this->redis('subscribe')->clean($sess_id);
			return true;
		}
	}

	//APP新模式，无需点击提交按钮，直接点即保存
	function settingUpdate($account, $channel, $option, $value=null, $action='add'){

		if(!$account || !$channel || !$option)return false;

		if($option == 'setting_ablumcat'){
			$ablumcat_option = C('options', 'subscribe_setting_ablumcat');
			if(!isset($ablumcat_option[$value]))return false;
		}

		if($option == 'setting_clothes_size_girl' || $option == 'setting_clothes_size_boy' || $option == 'setting_shoes_size_girl' || $option == 'setting_shoes_size_boy'){
			$setting_option = C('options', 'subscribe_'.$option);
			if(!isset($setting_option[$value]))return false;
		}

		$exist = $this->detail($account, $channel);
		if($exist){
			$old_value = array();
			if($exist && $exist[$option]){
				$old_value = array_flip(explode(',', $exist[$option]));
			}

			if($action == 'add'){
				$old_value[$value] = 1;
			}else{
				unset($old_value[$value]);
				if(!count($old_value))$old_value = array();
			}

			$new_value = join(',', array_keys($old_value));
			if(!$new_value)$new_value = '';

			$ret = $this->update($account, $channel, array($option=>$new_value));

		}else if($action == 'add'){

			$setting = array();
			if(isset($_GET['push_token']))$setting['push_token'] = $_GET['push_token'];
			$setting['updatetime'] = date('Y-m-d H:i:s');
			$setting['status'] = \DB\Subscribe::STATUS_NORMAL;
			if($value)$setting[$option] = $value;
			$ret = $this->db('promotion.subscribe')->add($account, $channel, $setting);

		}else{
			$ret = true;
		}

		return $ret;
	}

	//自动创建新用户设置
	function settingAutoCreated($device_id, $platform){

		$all_ablumcat = array_keys(C('options', 'subscribe_setting_ablumcat'));
		foreach($all_ablumcat as $value){
			$this->settingUpdate($device_id, $platform, 'setting_ablumcat', $value);
		}
		return true;
	}

	//快速保存token
	function savePushToken($account, $channel, $token){

		if(!$account || !$channel || !$token)return;

		if(valid($account, 'device_id') && in_array($channel, array('ios','android')) && valid($token, 'push_token')){
			return $this->update($account, $channel, array('push_token' => $token));
		}
	}

	//退订订阅(允许首次就是退订状态)
	function refuse($account, $channel='email'){

		if(!$account || !$channel)return;

		if(!$this->detail($account, $channel)){
			//$ret = $this->db('promotion.subscribe')->add($account, $channel, array('status'=>\DB\Subscribe::STATUS_STOP, 'updatetime'=>date('Y-m-d H:i:s')));
		}else{
			$ret = $this->update($account, $channel, array('status'=>\DB\Subscribe::STATUS_STOP, 'updatetime'=>date('Y-m-d H:i:s')));
		}
		return true;
	}

	//保存订阅号关联的支付宝
	function saveAlipay($account, $channel, $alipay){

		if(!$account || !$channel || !$alipay)return;
		$ret = $this->update($account, $channel, array('alipay'=>$alipay));
		return $ret;
	}

	//保存订阅号关联的支付宝
	function getAlipay($account, $channel='email'){

		if(!$account || !$channel)return;
		return $this->detail($account, $channel, 'alipay');
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

		$times_open = $this->detail($account, $channel, 'times_open');
		$times_open += 1;
		$this->update($account, $channel, array('times_open'=>$times_open));

		if($channel == 'email' && $detail['task_id']){
			$edm_detail = D('edm')->detail($detail['task_id']);
			if($edm_detail){
				$edm_times_open = $edm_detail['times_open'];
				$edm_times_open++;
				D('edm')->update($detail['task_id'], array('times_open'=>$edm_times_open));
			}
		}

		//标识用户打开了信息，进入队列计算notify_num推送更新后的消息数
		$this->sendAppOpenMsg($account, $channel);
		return true;
	}

	//标识通知已经点击
	function markMessageClicked($account, $channel, $message_id){

		$detail = $this->db('promotion.subscribe_message')->detail($account, $channel, $message_id);
		if($channel == 'email' && $detail['task_id']){
			$edm_detail = D('edm')->detail($detail['task_id']);
			if($edm_detail){
				$edm_times_click = $edm_detail['times_click'];
				$edm_times_click++;
				D('edm')->update($detail['task_id'], array('times_click'=>$edm_times_click));
			}
		}
	}

	//标识消息已经推送
	function markMessagePushed($account, $channel, $message_id, $succ=true, $err_msg=''){

		if(!$account || !$channel || !$message_id)return;

		$detail = $this->db('promotion.subscribe')->detail($account, $channel);

		if($succ){
			$times_push_succ = $detail['times_push_succ'] + 1;
			$this->update($account, $channel, array('times_push_succ'=>$times_push_succ, 'pushtime'=>date('Y-m-d H:i:s')));
			$this->db('promotion.subscribe_message')->update($account, $channel, $message_id, array('status'=>\DB\SubscribeMessage::STATUS_SUCC, 'pushtime'=>date('Y-m-d H:i:s')));
		}else{
			$times_push_fail = $detail['times_push_fail'] + 1;
			$this->update($account, $channel, array('times_push_fail'=>$times_push_fail, 'pushtime'=>date('Y-m-d H:i:s')));
			$this->db('promotion.subscribe_message')->update($account, $channel, $message_id, array('status'=>\DB\SubscribeMessage::STATUS_FAIL, 'err_msg'=>$err_msg, 'pushtime'=>date('Y-m-d H:i:s')));
		}
	}

	//获取待推送消息
	function getWaitPushMessageList($channel='email', $limit=100){

		return $this->db('promotion.subscribe_message')->getList(array('status'=>\DB\SubscribeMessage::STATUS_WAIT, 'channel'=>$channel), $limit);
	}

	//读取订阅消息列表
	function getMessageList($account, $channel, $cond=array(), $limit=10){

		if(!$account || !$channel)return;
		$cond['account'] = $account;
		$cond['channel'] = $channel;
		return $this->db('promotion.subscribe_message')->getList($cond, $limit);
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

	//获取今日等待推送特卖的用户数
	function getNeedPushMemberNum($candition=array()){

		return D('subscribe')->db('promotion.subscribe')->findCount(array('status'=>\DB\Subscribe::STATUS_NORMAL, 'pushtime'=>'<= '.date('Y-m-d', strtotime(date('Y-m-d'))-DAY*(C('comm', 'subscribe_push_space')-1)), 'createtime'=>'<= '.date('Y-m-d 00:00:00'))+$candition);
	}

	//获取待推送的候选特卖
	function getCandidatePromo(){

		$this->db('promotion.subscribe_cand');
		$promo_candidator = $this->db('promotion.subscribe_cand')->findAll(array('status'=>\DB\SubscribeCand::STATUS_NORMAL, 'createdate'=>'>= '.date('Y-m-d', time()-DAY*C('comm', 'subscribe_push_space'))));
		return clearTableName($promo_candidator);
	}

	//获取待推送会员
	function getWaitPushCandidateMembers($limit=1000, $page=1, $candition=array()){


		$ret = $this->db('promotion.subscribe')->findAll(array('status'=>\DB\Subscribe::STATUS_NORMAL, 'pushtime'=>'<= '.date('Y-m-d', strtotime(date('Y-m-d'))-DAY*(C('comm', 'subscribe_push_space')-1)), 'createtime'=>'<= '.date('Y-m-d 00:00:00'))+$candition, 'account,channel', 'id ASC', $limit, $page);
		return clearTableName($ret);
	}

	//更新候选特卖
	function updatePromoCand($sp, $goods_id, $data){

		if(!$sp ||!$goods_id ||!$data)return;
		return D('promotion')->db('promotion.subscribe_cand')->update($sp, $goods_id, $data);
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

	//增加订阅反馈
	function addFeedback($data, $account='', $channel=''){

		if(!$data)return;
		return $this->db('promotion.subscribe_feedback')->add($data);
	}

	function getWaitPushMessage($condition=array()){

		$this->db('promotion.subscribe_message');
		$condition['status'] = array(\DB\SubscribeMessage::STATUS_WAIT, \DB\SubscribeMessage::STATUS_FAIL);
		return $this->db('promotion.subscribe_message')->getList($condition);
	}
}
?>