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

		$ret = $this->db('promotion.subscribe_ablum')->update($id, $data);
		if($ret){
			$key = 'ablum:detail:'.$id;
			D('cache')->clean($key);
		}

		return $ret;
	}

	/**
	 * 获取专辑列表
	 * @param  array   $condition 搜索条件(status, title, sp, ablumcat-arr, brand-arr, tag-arr)
	 * @param  object  $pn        分页组件对象
	 * @param  integer $show      每页显示几条
	 * @param  integer $maxPages  最大页数
	 * return  array              订单数据
	 */
	function getList($pn, $condition=array(), $show = 5, $dir='DESC') {

		if(!isset($condition['status']))$condition['status'] = 1;
		$condition = arrayClean($condition);
		$condition_build = array();

		if(isset($condition['setting_ablumcat']) && $condition['setting_ablumcat'] && count($condition['setting_ablumcat']) == count(C('options', 'subscribe_setting_ablumcat'))){
			unset($condition['setting_ablumcat']);
		}

		//ablumcat关联个性设置
		//没有1 置空 setting_clothes_style_girl & setting_clothes_size_girl
		//没有2 置空 setting_shoes_size_girl
		//没有5 置空 setting_clothes_style_boy & setting_clothes_size_boy
		//没有6 置空 setting_shoes_size_boy
		if(!isset($condition['setting_ablumcat']) || !in_array(1, $condition['setting_ablumcat'])){
			unset($condition['setting_clothes_style_girl']);
			unset($condition['setting_clothes_size_girl']);
		}
		if(!isset($condition['setting_ablumcat']) || !in_array(2, $condition['setting_ablumcat'])){
			unset($condition['setting_shoes_size_girl']);
		}
		if(!isset($condition['setting_ablumcat']) || !in_array(5, $condition['setting_ablumcat'])){
			unset($condition['setting_clothes_style_boy']);
			unset($condition['setting_clothes_size_boy']);
		}
		if(!isset($condition['setting_ablumcat']) || !in_array(6, $condition['setting_ablumcat'])){
			unset($condition['setting_shoes_size_boy']);
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

			list($order, $limit, $page) = $pn->init($condition, array('modelClass' => $this->db('promotion.subscribe_ablum')));
			if(@$_GET['page']>$pn->paging['pageCount'])return array();
			$result = $this->db('promotion.subscribe_ablum')->findAll($condition, 'id', $order, $limit, $page);
		}else{
			$result = $this->db('promotion.subscribe_ablum')->findAll($condition, 'id', 'id DESC', $show);
		}

		$result = clearTableName($result);
		if(!$result)return array();
		$ret = array();
		foreach($result as $line){
			$ret[] = $this->detail($line['id']);
		}
		return $ret;
	}

	//获取专辑详情
	function detail($id, $field=''){

		if(!$id)return;
		$key = 'ablum:detail:'.$id;
		$cache = D('cache')->get($key);
		if($cache && 0){
			$detail = D('cache')->ret($cache);
		}else{
			$detail = $this->db('promotion.subscribe_ablum')->find(array('id'=>$id));
			$detail = clearTableName($detail);
			if(!$detail)return;
			$serial_fields = array('setting_ablumcat', 'setting_brand', 'setting_clothes_style_girl', 'setting_clothes_style_boy', 'setting_clothes_size_girl', 'setting_shoes_size_girl', 'setting_clothes_size_boy', 'setting_shoes_size_boy');
			foreach($serial_fields as $f){
				if($detail[$f])
					$detail[$f] = arrayClean(explode(',', $detail[$f]));
				else
					$detail[$f] = array();
			}
			if($detail['url_sclick']){
				$detail['link'] = $detail['url_sclick'];
			}else{
				$detail['link'] = $detail['url'];
			}

			if(!$detail['status'])$detail['expire'] = '活动已结束';
			if(!isset($detail['expire']) && $detail['expire_start'] && $detail['expire_start'] != '0000-00-00 00:00:00'){
				$diff = timeDiff(strtotime($detail['expire_start']));
				if($diff != -1){
					$detail['expire'] = '还有'.$diff.'开始';
				}
			}
			if(!isset($detail['expire']) && $detail['expire_end'] && $detail['expire_end'] != '0000-00-00 00:00:00'){
				$diff = timeDiff(strtotime($detail['expire_end']));
				if($diff != -1){
					$detail['expire'] = '剩余：'.$diff;
				}else{
					$detail['expire'] = '活动已结束';
				}
			}

			$imgsize = $this->db('files')->field('imgsize', array('filepath'=>$detail['cover_1']));
			if($imgsize){
				list($detail['cover_width'], $detail['cover_height']) = explode('x', $imgsize);
			}

			foreach($detail['setting_brand'] as $brand_id){
				$brand_names[] = D('brand')->getName($brand_id, false);
			}

			if($detail['cover_width']/$detail['cover_height'] <= 1.28){
				$detail['more'] = true;
			}

			$brand_names = join(',', $brand_names);
			$detail['brand_names'] = $brand_names;
			D('cache')->set($key, $detail, MINUTE*10, true);
		}

		if($field)
			return $detail[$field];
		else
			return $detail;
	}

	//获取最新专辑(APP一打开)，is_new区分是否用户下拉获取未读过的专辑
	function getNewAblum($account, $channel, $is_new=false){

		if(!$account || !$channel)return;

		//读取账号订阅setting
		$condition = $this->getAblumCondition($account, $channel);

		if($is_new){
			$readed_ids = $this->redis('ablum')->getReaded($account, $channel);
			if($readed_ids)
				$condition['id'] = "not in (".join(',', $readed_ids).")";
		}

		if($is_new){
			$lists = $this->getList(null, $condition, 3); //查找更多
			shuffle($lists);
		}else{
			$lists = $this->getList(null, $condition, 3); //首次加载
		}

		if($lists){
			$ablum_ids = array();
			foreach($lists as $list){
				$ablum_ids[] = $list['id'];
			}

			$this->redis('ablum')->markReaded($account, $channel, $ablum_ids);
		}

		return $lists;
	}

	//获取向下专辑(APP下边界触发)
	function getOldAblum($account, $channel){

		if(!$account || !$channel)return;

		$display_ablums_min_id = $_GET['display_ablums_min_id'];

		$readed_ablums = $this->redis('ablum')->getReaded($account, $channel);
		rsort($readed_ablums);
		if($readed_ablums){
			$i = 0;
			$ret_ablums_ids = array();
			foreach ($readed_ablums as $ablum_id) {
				if($ablum_id < $display_ablums_min_id){
					if($i>3)break;
					$ret_ablums_ids[] = $ablum_id;
					$i++;
				}
			}
		}

		if($ret_ablums_ids){
			$ret = array();
			foreach($ret_ablums_ids as $ablum_id){
				$ret[] = $this->detail($ablum_id);
			}
			return $ret;
		}else{
			return $this->getNewAblum($account, $channel, true);
		}
	}

	//根据订阅设置获取检索条件
	function getAblumCondition($account, $channel){

		if(!$account || !$channel)return;

		$setting = D('subscribe')->getSetting($account, $channel);
		if(!$setting)return array();

		$condition = array();
		$condition['status'] = 1;
		$condition['setting_ablumcat'] = $setting['setting_ablumcat'];
		$condition['setting_clothes_style_girl'] = $setting['setting_clothes_style_girl'];
		$condition['setting_clothes_style_boy'] = $setting['setting_clothes_style_boy'];
		$condition['setting_clothes_size_girl'] = $setting['setting_clothes_size_girl'];
		$condition['setting_clothes_size_boy'] = $setting['setting_clothes_size_boy'];
		$condition['setting_shoes_size_girl'] = $setting['setting_shoes_size_girl'];
		$condition['setting_shoes_size_boy'] = $setting['setting_shoes_size_boy'];

		return $condition;
	}
}
?>