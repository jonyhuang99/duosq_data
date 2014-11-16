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
	function update($account, $channel, $album_id, $data){

		if(!$account || !$channel || !$album_id)return false;

		$detail = $this->detail($account, $channel, $album_id);
		if(!$detail)return false;

		$ret = parent::update($detail['id'], $data);
		return $ret;
	}

	//新增候选数据
	function add($account, $channel, $album_id){

		if(!$account || !$channel || !$album_id)return false;
		$id = $this->detail($account, $channel, $album_id);
		if($id)return false;

		$ret = parent::add(array('account'=>$account, 'channel'=>$channel, 'album_id'=>$album_id));
		return $ret;
	}

	//获取收藏详情
	function detail($account, $channel, $album_id){

		$ret = $this->find(array('account'=>$account, 'channel'=>$channel, 'album_id'=>$album_id));
		return clearTableName($ret);
	}
}
?>