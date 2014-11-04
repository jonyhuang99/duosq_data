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

	//获取消息列表，提供给APP/微信使用，或者获取待推送列表
	function getList($cond=array(), $limit=10){

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
	function add($account, $channel='email', $title, $message, $task_id){

		if(!$account || !$channel || !$title ||!$message ||!$task_id)return false;

		$data['account'] = $account;
		$data['channel'] = $channel;
		$data['title'] = $title;
		$data['task_id'] = $task_id;
		$data['message'] = is_array($message)?serialize($message):$message;
		return parent::add($data);
	}

	//更新推送消息
	function update($account, $channel='email', $message_id, $data){

		if(!$account || !$channel || !$message_id)return false;
		$data = arrayClean($data);
		if(!$data)return false;

		$ret = parent::update($message_id, $data);
		return $ret;
	}

	//置空find/finaAll，只允许从detail进入，后续会改成分表
	function find(){}
	function findAll(){}
}
?>