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
	function update($account, $channel, $type, $id, $data){

		if(!$account || !$channel || !$type || !$id)return false;

		$detail = $this->detail($account, $channel, $type, $id);
		if(!$detail)return false;

		$ret = parent::update($detail['id'], $data);
		return $ret;
	}

	//新增候选数据
	function add($account, $channel, $type, $id){

		if(!$account || !$channel || !$type || !$id)return false;
		$detail = $this->detail($account, $channel, $type, $id);
		if($detail)return false;

		if($type == 'album')
			$ret = parent::add(array('account'=>$account, 'channel'=>$channel, 'type'=>$type, 'album_id'=>$id));
		elseif($type == 'goods')
			$ret = parent::add(array('account'=>$account, 'channel'=>$channel, 'type'=>$type, 'goods_id_str'=>$id));

		return $ret;
	}

	//获取收藏详情
	function detail($account, $channel, $type, $id){

		if($type == 'album'){
			$ret = $this->find(array('account'=>$account, 'channel'=>$channel, 'type'=>$type, 'album_id'=>$id));
		}elseif($type == 'goods'){
			$ret = $this->find(array('account'=>$account, 'channel'=>$channel, 'type'=>$type, 'goods_id_str'=>$id));
		}
		return clearTableName($ret);
	}
}
?>