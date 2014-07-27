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

	//读取订阅设置
	function getSetting($account, $channel='email'){

		if(!$account)return false;
		$ret = $this->db('promotion.subscribe')->detail($account, $channel);
		if(!$ret)return false;

		//清掉和配置无关的字段
		foreach($ret as $option => $val){
			if(stripos($option, 'setting')===false){
				unset($ret[$option]);
			}
		}

		if($ret['setting_brand'])
			$ret['setting_brand'] = explode(',', $ret['setting_brand']);
		else{
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

		return $ret;
	}

	//初始化指定会话的全部配置
	function sessInit($sess_id, $setting){

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

		foreach($setting as $key => $value){

			$this->redis('subscribe')->set($sess_id, $key, $value);
		}
		return true;
	}

	//从订阅会话中，更改指定option的值，或删除指定option
	function sessUpdate($sess_id, $option, $value=null, $action='add'){

		if(!$this->sessCheck($sess_id) || !$option)return false;

		switch ($option) {
			case 'setting_subcat':
			case 'setting_midcat':
			case 'setting_brand':
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
			case 'setting_shoes_size_girl':
			case 'setting_shoes_size_boy':
				$sess_setting = $value;
				break;
		}

		$ret = $this->redis('subscribe')->set($sess_id, $option, $sess_setting);
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

		if($exist){
			//用空来覆盖旧配置
			foreach ($exist as $key => $value) {
				if(strpos($key, 'setting')!==false){
					if(!isset($setting[$key])){
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
	function markOpened($account, $channel, $message_ids){

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
		return true;
	}

	//读取订阅消息列表
	function getMessageList($account, $channel, $cond=array(), $limit=10){
		if(!$account || !$channel)return false;

		return $this->db('promotion.subscribe_message')->getList($account, $channel, $cond, $limit);
	}
}
?>