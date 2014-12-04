<?php
//特卖标签操作基类
namespace DB;

class QueuePromo2tag extends _Db {

	var $name = 'QueuePromo2tag';
	var $useDbConfig = 'promotion';

	//新增特卖信息分类数据
	function add($data){

		if(!$data['sp'] || !$data['goods_id'])return;
		return parent::add($data);
	}

	//更新特卖信息标签数据
	function update($sp, $goods_id, $data){

		if(!$sp || !$goods_id || !$data)return;

		$hits = $this->findAll(array('sp'=>$sp, 'goods_id'=>$goods_id));
		$hit_ids = array();
		$hits = clearTableName($hits);
		if(!$hits)return;
		foreach($hits as $hit){
		 	$ret = parent::update($hit['id'], $data);
		}
		return $ret;
	}

	//删除特卖标签
	function delete($sp, $goods_id, $tag=''){

		if(!$sp || !$goods_id)return;

		if($tag){
			$this->query("DELETE FROM duosq_promotion.queue_promo2tag WHERE sp = '{$sp}' AND goods_id = '{$goods_id}' AND tag = '{$tag}'");
		}else{
			$this->query("DELETE FROM duosq_promotion.queue_promo2tag WHERE sp = '{$sp}' AND goods_id = '{$goods_id}'");
		}
	}

	//删除非类别内的特卖排序
	function deleteNotIn($sp, $goods_id, $not_in_subcat){

		if(!$sp || !$goods_id || !$not_in_subcat)return;

		$not_in = "NOT IN ('".join("','", $not_in_subcat)."')";
		$this->query("DELETE FROM duosq_promotion.queue_promo2tag WHERE sp = '{$sp}' AND goods_id = '{$goods_id}' AND subcat {$not_in}");
	}

	//取出特卖的标签
	function get($sp, $goods_id, $subcat=''){

		if($subcat){
			$tag_data = $this->findAll(array('sp'=>$sp, 'goods_id'=>$goods_id, 'subcat'=>$subcat));
		}else{
			$tag_data = $this->findAll(array('sp'=>$sp, 'goods_id'=>$goods_id));
		}

		if(!$tag_data)return array();

		$tag_data = clearTableName($tag_data);
		$tags = array();
		foreach ($tag_data as $data) {
			$tags[$data['subcat']][] = $data['tag'];
		}

		return $tags;
	}
}
?>