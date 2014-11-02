<?php
//收藏专辑数据
namespace DB;

class SubscribeCang extends _Db {

	var $name = 'SubscribeCang';
	var $useDbConfig = 'promotion';

	//专辑状态定义
	const STATUS_NORMAL = 1; //正常
	const STATUS_INVALID = 0; //无效

	//修改候选数据
	function update($account, $channel, $ablum_id, $data){

		if(!$account || !$channel || !$ablum_id)return false;

		$detail = $this->detail($account, $channel, $ablum_id);
		if(!$detail)return false;

		$ret = parent::update($detail['id'], $data);
		return $ret;
	}

	//新增候选数据
	function add($account, $channel, $ablum_id){

		if(!$account || !$channel || !$ablum_id)return false;
		$id = $this->detail($account, $channel, $ablum_id);
		if($id)return false;

		$ret = parent::add(array('account'=>$account, 'channel'=>$channel, 'ablum_id'=>$ablum_id));
		return $ret;
	}

	//获取收藏详情
	function detail($account, $channel, $ablum_id){

		$ret = $this->find(array('account'=>$account, 'channel'=>$channel, 'ablum_id'=>$ablum_id));
		return clearTableName($ret);
	}
}
?>