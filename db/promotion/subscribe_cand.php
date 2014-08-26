<?php
//订阅候选数据表操作基类
namespace DB;

class SubscribeCand extends _Db {

	var $name = 'SubscribeCand';
	var $useDbConfig = 'promotion';

	//订阅候选特卖状态定义
	const STATUS_NORMAL = 1; //特卖正常
	const STATUS_INVALID = 0; //特卖无效

	//返回候选数据详情
	function detail($sp, $goods_id, $field=''){

		if(!$sp || !$goods_id)return false;

		$ret = $this->find(array('sp'=>$sp, 'goods_id'=>$goods_id));
		$ret = clearTableName($ret);
		if($field){
			return $ret[$field];
		}else{
			return $ret;
		}
	}

	//修改候选数据
	function update($sp, $goods_id, $data){

		if(!$sp || !$goods_id || !$data)return false;

		$id = $this->detail($sp, $goods_id, 'id');
		if(!$id)return false;

		$ret = parent::update($id, $data);
		return $ret;
	}

	//新增候选数据
	function add($data){

		if(!$data['sp'] || !$data['goods_id'])return false;

		$id = $this->detail($data['sp'], $data['goods_id']);
		if($id)return false;

		$data['validate'] = date('Y-m-d');
		$ret = parent::add($data);
		return $ret;
	}
}
?>