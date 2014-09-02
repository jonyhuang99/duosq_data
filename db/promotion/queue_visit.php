<?php
//商品操作基类
namespace DB;

class QueueVisit extends _Db {

	var $name = 'QueueVisit';
	var $useDbConfig = 'promotion';

	//标识商品被访问
	function visited($sp, $goods_id){

		if(!$sp || !$goods_id)return;

		if($qid = $this->field('id', array('sp'=>$sp, 'goods_id'=>$goods_id))){
			return parent::update($qid, array('visit_date'=>date('Y-m-d')));
		}

		return parent::add(array('sp'=>$sp, 'goods_id'=>$goods_id, 'visit_date'=>date('Y-m-d')));
	}

	//标识该商品今日已进行过价格探测
	function detected($sp, $goods_id, $succ=1){

		if(!$sp || !$goods_id)return;

		return $this->query("UPDATE queue_visit SET price_update = '".date('Y-m-d')."', price_ret = '".$succ."' WHERE sp = '{$sp}' AND goods_id = '{$goods_id}'");
	}

	//获取指定时间段被访问过的商品
	function getLastVisit($days=''){

		if(!$days)$days = 90;
		$visit_date = date('Y-m-d', time() - DAY*$days);
		$ret = $this->findAll("visit_date > '{$visit_date}' AND price_update < '".date('Y-m-d')."'", '', 'visit_date DESC, id DESC', 3000);
		return clearTableName($ret);
	}
}
?>