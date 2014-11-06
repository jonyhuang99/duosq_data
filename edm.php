<?php
//DAL:EDM模块
namespace DAL;

class Edm extends _Dal {

	//增加EDM
	function add($data){
		return $this->db('promotion.subscribe_task_edm')->add($data);
	}

	//更新EDM
	function update($id, $data){

		$ret = $this->db('promotion.subscribe_task_edm')->update($id, $data);
		return $ret;
	}

	/**
	 * 获取EDM列表
	 * @param  array   $condition 搜索条件(status, title, sp, brand-arr, setting-arr)
	 * @param  object  $pn        分页组件对象
	 * @param  integer $show      每页显示几条
	 * @param  integer $maxPages  最大页数
	 * return  array              订单数据
	 */
	function getList($pn, $condition=array(), $show = 5, $dir='DESC') {

		if(!$condition)$condition['status'] = 1;
		$condition = arrayClean($condition);
		$condition_build = array();

		if(isset($condition['setting_ablumcat']) && $condition['setting_ablumcat'] && count($condition['setting_ablumcat']) == count(C('options', 'subscribe_setting_ablumcat'))){
			unset($condition['setting_ablumcat']);
		}
		foreach($condition as $field => $value){
			if(!$value)continue;

			switch ($field) {
				case 'id':
					$condition_build[] = "id {$value}";
					break;
				case 'title':
					$condition_build[] = "{$field} like '%{$value}%'";
					break;
				case 'setting_ablumcat':
				case 'setting_clothes_style_girl':
				case 'setting_clothes_style_boy':
				case 'setting_clothes_size_girl':
				case 'setting_clothes_size_boy':
				case 'setting_shoes_size_girl':
				case 'setting_shoes_size_boy':
					$condition_build[] = "{$field} like '%" . join(",%' or {$field} like '%", $value) . ",%' or {$field} = ''";
					break;
				default:
					$condition_build[] = "{$field}='{$value}'";
					break;
			}
		}
		$condition = '(' . join(") and (", $condition_build) . ')';

		if($pn){
			//page = 0 返回总页数
			$pn->show = $show;
			$pn->orderby = 'id';
			$pn->direction = $dir;
			$pn->maxPages = 20;

			list($order, $limit, $page) = $pn->init($condition, array('modelClass' => $this->db('promotion.subscribe_task_edm')));
			if(@$_GET['page']>$pn->paging['pageCount'])return array();
			$result = $this->db('promotion.subscribe_task_edm')->findAll($condition, 'id', $order, $limit, $page);
		}else{
			$result = $this->db('promotion.subscribe_task_edm')->findAll($condition, 'id', 'id DESC', $show);
		}

		$result = clearTableName($result);
		if(!$result)return array();
		$ret = array();
		foreach($result as $line){
			$ret[] = $this->detail($line['id']);
		}
		return $ret;
	}

	//获取EDM详情
	function detail($id, $field=''){

		if(!$id)return;
		$detail = $this->db('promotion.subscribe_task_edm')->find(array('id'=>$id));
		$detail = clearTableName($detail);

		$serial_fields = array('setting_ablumcat', 'setting_clothes_style_girl', 'setting_clothes_style_boy', 'setting_clothes_size_girl', 'setting_shoes_size_girl', 'setting_clothes_size_boy', 'setting_shoes_size_boy');

		foreach($serial_fields as $f){
			if($detail[$f])
				$detail[$f] = arrayClean(explode(',', $detail[$f]));
			else
				$detail[$f] = array();
		}

		if(!$detail)return;

		if($field)
			return $detail[$field];
		else
			return $detail;
	}

	//标识已向用户发过的EDM
	function markSentId($account, $channel, $edm_id){
		if(!$account|| !$channel || !$edm_id)return;
		return $this->redis('edm')->markSentId($account, $channel, $edm_id);
	}

	//获取已向用户发过的EDM
	function getSentId($account, $channel){
		if(!$account|| !$channel)return;
		return $this->redis('edm')->getSentId($account, $channel);
	}

	//去除已向用户发过的EDM标识
	function delSentId($account, $channel, $edm_id){
		if(!$account|| !$channel || !$edm_id)return;
		return $this->redis('edm')->delSentId($account, $channel, $edm_id);
	}
}
?>