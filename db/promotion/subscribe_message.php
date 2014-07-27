<?php
//订阅推送信息操作基类
namespace DB;

class SubscribeMessage extends _Db {

	var $name = 'SubscribeMessage';
	var $useDbConfig = 'promotion';

	//推送状态定义
	const STATUS_WAIT = 0; //等待推送
	const STATUS_SUCC = 1; //推送成功
	const STATUS_FAIL = 2; //推送失败
	const STATUS_SKIP = 3; //彻底失败(不再尝试)
	const STATUS_OPENED = 4; //成功打开

	//获取待推送消息
	function getWaitPush($account, $channel='email', $limit=1){

		if(!$account)return false;
		$ret = parent::findAll(array('account'=>$account, 'channel'=>$channel, 'status'=>self::STATUS_WAIT), '', 'id DESC', $limit);
		$ret = clearTableName($ret);
		return $ret;
	}

	//获取消息列表，提供给APP/微信使用
	function getList($account, $channel='email', $cond=array(), $limit=10){

		if(!$account || !$channel)return false;
		$cond['account'] = $account;
		$cond['channel'] = $channel;
		$cond = arrayClean($cond);
		$ret = parent::findAll($cond, '', 'id DESC', $limit);
		$ret = clearTableName($ret);
		return $ret;
	}

	//获取指定消息内容
	function detail($account, $channel='email', $message_id){

		if(!$account || !$channel || !$message_id)return false;

		$ret = parent::findAll(array('id'=>$message_id));
		if(empty($ret[0]))return false;
		$ret = clearTableName($ret[0]);
		return $ret;
	}

	//增加推送消息
	function add($account, $channel='email', $title, $message){

		if(!$account || !$channel || !$title ||!$message)return false;
		$this->create();
		$data['account'] = $account;
		$data['channel'] = $channel;
		$data['title'] = $title;
		$data['message'] = is_array($message)?serialize($message):$message;
		return parent::save($data);
	}

	//更新推送消息
	function update($account, $channel='email', $message_id, $data){

		if(!$account || !$channel || !$message_id)return false;
		$data = arrayClean($data);
		if(!$data)return false;

		$data['id'] = $message_id;

		$ret = parent::save($data);
		return $ret;
	}

	//置空save，只允许从add/update进入
	function save(){}

	//置空find/finaAll，只允许从detail进入，后续会改成分表
	function find(){}
	function findAll(){}
}
?>