<?php
//队列消息操作底层

namespace REDIS;

class Queue extends _Redis {

	protected $namespace = 'queue';
	protected $dsn_type = 'database';

	static $QUEUE_DATA = array();

	const KEY_BALANCE = 'fund'; //资产变更队列

	/**
	 * 增加消息，静态缓存
	 * @param string  $key  消息类型
	 * @param string  $msg  消息内容
	 */
	function add($key, $msg){

		if(!$key || !$msg)return;
		self::$QUEUE_DATA[] = array('key'=>$key, 'msg'=>$msg);
		return true;
	}

	/**
	 * 自我阻塞方式获取消息
	 * @param  string  $key  消息类型
	 * @return string        消息内容
	 */
	function bget($key){

		if(!$key)return;
		$ret = $this->brpoplpush($key, $key.':doing', 5); //5秒超时
		return $ret;
	}

	/**
	 * 完成消息任务后，清除消息任务进行中标识
	 * @param string  $key  消息类型
	 * @param string  $msg  消息内容
	 */
	function done($key, $msg){

		if(!$key || !$msg)return;
		$ret = $this->lrem($key.':doing', $msg, -1);
		return $ret;
	}

	/**
	 * 提交静态缓存中的消息
	 * @return [type] [description]
	 */
	function __destruct(){

		if(self::$QUEUE_DATA){
			foreach(self::$QUEUE_DATA as $data){
				$this->lpush($data['key'], $data['msg']);
			}
		}
		return true;
	}
}
?>