<?php
//DAL:收藏夹模块
namespace DAL;

class Cang extends _Dal {

	//增加专辑
	function add($account, $channel, $type, $id){

		if(!$account ||!$channel || !$type || !$id)return;

		if($this->detail($account, $channel, $type, $id)){
			$ret = $this->db('promotion.subscribe_cang')->update($account, $channel, $type, $id, array('status'=>\DB\SubscribeCang::STATUS_NORMAL));
		}else{
			$ret = $this->db('promotion.subscribe_cang')->add($account, $channel, $type, $id);
		}

		if($ret){
			$key = 'cang:detail:account:'.$account.':channel:'.$channel.':type:'.$type.':id:'.$id;
			D('cache')->clean($key);
		}

		return $ret;
	}

	//删除专辑
	function del($account, $channel, $type, $id){

		if(!$account ||!$channel || !$type ||!$id)return;

		$ret = $this->db('promotion.subscribe_cang')->update($account, $channel, $type, $id, array('status'=>\DB\SubscribeCang::STATUS_INVALID));

		if($ret){
			$key = 'cang:detail:account:'.$account.':channel:'.$channel.':type:'.$type.':id:'.$id;
			D('cache')->clean($key);
		}

		return $ret;
	}

	//判断用户是否收藏了该专辑
	function has($account, $channel, $type, $id){

		if(!$account ||!$channel || !$type ||!$id)return;

		if($this->detail($account, $channel, $type, $id, 'status')){
			return true;
		}
	}

	/**
	 * 获取收藏的专辑列表
	 * @param  array   $condition 搜索条件(status, channel, account)
	 * @param  object  $pn        分页组件对象
	 * @param  integer $show      每页显示几条
	 * @param  integer $maxPages  最大页数
	 * return  array              订单数据
	 */
	function getList($pn, $type, $condition=array(), $show = 4, $dir='DESC') {

		if(!$type)return;
		//page = 0 返回总页数
		$pn->show = $show;
		$pn->orderby = 'id';
		$pn->direction = $dir;
		$pn->maxPages = 20;

		$condition['type'] = $type;
		list($order, $limit, $page) = $pn->init($condition, array('modelClass' => $this->db('promotion.subscribe_cang')));
		if(@$_GET['page']>$pn->paging['pageCount'])return array();
		$result = $this->db('promotion.subscribe_cang')->findAll($condition, 'album_id,goods_id_str', $order, $limit, $page);
		$result = clearTableName($result);
		if(!$result)return array();

		$ret = array();
		foreach($result as $line){
			if($type == 'album'){
				$detail = D('album')->detail($line['album_id']);
			}elseif($type == 'goods'){
				list($sp, $goods_id) = explode(':', $line['goods_id_str']);
				$goods_detail = D('promotion')->goodsDetail($sp, $goods_id);
				$promo_detail = D('promotion')->promoDetail($sp, $goods_id);
				$detail = array_merge($goods_detail, (array)$promo_detail);
			}

			$ret[] = $detail;
		}
		return $ret;
	}

	//获取收藏详情
	function detail($account, $channel, $type, $id, $field=''){

		if(!$account ||!$channel ||!$type ||!$id)return;
		$key = 'cang:detail:account:'.$account.':channel:'.$channel.':type:'.$type.':id:'.$id;
		$cache = D('cache')->get($key);
		if($cache){
			$detail = D('cache')->ret($cache);
		}else{
			$detail = $this->db('promotion.subscribe_cang')->detail($account, $channel, $type, $id);
			if(!$detail)return;
			D('cache')->set($key, $detail, MINUTE*10, true);
		}

		if($field)
			return $detail[$field];
		else
			return $detail;
	}
}
?>