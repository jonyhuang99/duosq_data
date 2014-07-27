<?php
//订阅数据库操作基类
namespace DB;

class Subscribe extends _Db {

	var $name = 'Subscribe';
	var $useDbConfig = 'promotion';

	//订阅状态定义
	const STATUS_NORMAL = 1; //正常
	const STATUS_STOP = 0; //停止订阅

	//返回指定账户订阅信息
	function detail($account, $channel='email', $field=''){

		if(!$account)return false;
		$ret = parent::findAll(array('account'=>$account, 'channel'=>$channel));
		if(empty($ret[0]))return false;
		$ret = clearTableName($ret[0]);
		if($field){
			return $ret[$field];
		}else{
			return $ret;
		}
	}

	//增加订阅账户
	function add($account, $channel='email', $data=array()){

		if(!$account || !$channel)return false;
		$this->create();
		$data['account'] = $account;
		$data['channel'] = $channel;
		return parent::save($data);
	}

	//修改订阅账户配置
	function update($account, $channel='email', $data){

		if(!$account || !$channel)return false;
		$data = arrayClean($data);
		if(!$data)return false;

		$id = $this->detail($account, $channel, 'id');
		if(!$id)return false;
		$data['id'] = $id;

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