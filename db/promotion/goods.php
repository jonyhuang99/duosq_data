<?php
//商品操作基类
namespace DB;

class Goods extends _Db {

	var $name = 'Goods';
	var $useDbConfig = 'promotion';

	//商品状态定义
	const STATUS_NORMAL = 1; //正常
	const STATUS_SELL_OUT = 2; //售罄
	const STATUS_INVALID = 3; //下架|无效
	const STATUS_INVALID_FORCE = 4; //手动强制下架

	//置空save，只允许从add/update进入
	function save(){}

	//新增商品数据，返回商品ID
	function add($sp, $data){
		if(!$sp || !$data['sp'] || !$data['url_tpl'] || !$data['url_id'])return;

		$data['name'] = formatGoodsName($data['name']);
		$this->setTable($sp);

		if($goods_id = $this->field('id', array('sp'=>$data['sp'], 'url_tpl'=>$data['url_tpl'], 'url_id'=>$data['url_id']))){
			return $goods_id;
		}
		$this->create();
		return parent::save($data);
	}

	//更新商品数据
	function update($sp, $goods_id, $data){

		if(!$sp || !$goods_id)return;

		$this->setTable($sp);
		$data['id'] = $goods_id;
		return parent::save($data);
	}

	//获取商品详情
	function detail($sp, $goods_id, $field=false){

		if(!$sp || !$goods_id)return false;

		$this->setTable($sp);
		$ret = $this->find(array('id'=>$goods_id, 'sp'=>$sp));
		clearTableName($ret);

		if($field)return $ret[$field];

		return $ret;
	}

	//搜索商品
	function search($sp, $condition, $limit = 100){

		if(!$sp || !$condition)return false;
		$this->setTable($sp);

		if($limit == 1){
			$ret = $this->find($condition);
		}else{
			$ret = $this->findAll($condition);
		}

		return clearTableName($ret);
	}

	private function setTable($sp){
		$this->table = 'goods_' . ord(md5($sp.'_')) % 5;
		//$this->table = 'goods_0'; //测试用，TODO恢复
	}

	function setTableNo($no='0'){
		$this->table = 'goods_' . $no;
	}
}
?>