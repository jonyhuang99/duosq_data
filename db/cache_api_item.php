<?php
//操作淘宝API使用日志表
namespace DB;

class CacheApiItem extends _Db {

	var $name = 'CacheApiItem';

	function add($status=1, $sp, $api_errcode='', $p_id='', $content='', $key=''){

		$is_tmall = intval($content['is_tmall']);

		if($content['p_seller']){
			$seller = $content['p_seller'];
			unset($content['p_seller']);
		}else{
			$seller = '';
		}

		unset($content['is_tmall']);

		return parent::add(array('status'=>$status, 'sp'=>$sp, 'api_errcode'=>$api_errcode, 'p_id'=>$p_id, 'content'=>json_encode($content), 'is_tmall'=>$is_tmall, 'seller'=>$seller, 'ip'=>getIp(), 'key'=>$key));
	}
}
?>