<?php
//DAL:专辑模块
namespace DAL;

class Ablum extends _Dal {

	//增加专辑
	function add($data){
		return $this->db('promotion.subscribe_ablum')->add($data);
	}

	//更新专辑
	function update($id, $data){
		$key = 'ablum:detail:'.$id;
		D('cache')->clean($key);
		return $this->db('promotion.subscribe_ablum')->update($id, $data);
	}

	/**
	 * 获取专辑列表
	 * @param  array   $condition 搜索条件(title, sp, midcat, brand)
	 * @param  object  $pn        分页组件对象
	 * @param  integer $show      每页显示几条
	 * @param  integer $maxPages  最大页数
	 * return  array              订单数据
	 */
	function getList($pn, $condition=array(), $show = 10, $maxPages = 10) {

		if(!$condition)$condition['status'] = 1;
		$condition = arrayClean($condition);

		//page = 0 返回总页数
		$pn->show = $show;
		$pn->orderby = 'id';
		$pn->direction = 'desc';
		$pn->maxPages = $maxPages;

		list($order, $limit, $page) = $pn->init($condition, array('modelClass' => $this->db('promotion.subscribe_ablum')));

		$result = $this->db('promotion.subscribe_ablum')->findAll($condition, '', $order, $limit, $page);
		$result = clearTableName($result);
		foreach($result as &$line){
			$serial_fields = array('midcat', 'brand', 'tag_clothes_style_girl', 'tag_clothes_style_boy', 'tag_clothes_size_girl', 'tag_shoes_size_girl', 'tag_clothes_size_boy', 'tag_shoes_size_boy');
			foreach($serial_fields as $f){
				if($line[$f])
					$line[$f] = explode(',', $line[$f]);
				else
					$line[$f] = array();
			}
		}
		return $result;
	}

	//获取专辑详情
	function detail($id, $field=''){

		if(!$id)return;
		$key = 'ablum:detail:'.$id;
		$cache = D('cache')->get($key);
		if($cache){
			$detail = D('cache')->ret($cache);
		}else{
			$detail = $this->db('promotion.subscribe_ablum')->find(array('id'=>$id));
			$detail = clearTableName($detail);
			if(!$detail)return;
			$serial_fields = array('midcat', 'brand', 'tag_clothes_style_girl', 'tag_clothes_style_boy', 'tag_clothes_size_girl', 'tag_shoes_size_girl', 'tag_clothes_size_boy', 'tag_shoes_size_boy');
			foreach($serial_fields as $f){
				if($detail[$f])
					$detail[$f] = explode(',', $detail[$f]);
				else
					$detail[$f] = array();
			}
			D('cache')->set($key, $detail, MINUTE*10, true);
		}

		if($field)
			return $detail[$field];
		else
			return $detail;
	}
}
?>