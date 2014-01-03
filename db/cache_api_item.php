<?php
//操作淘宝API使用日志表
namespace DB;

class CacheApiItem extends _Db {

	var $name = 'CacheApiItem';

	function add($status=1, $sp, $api_errcode='', $p_id='', $content='', $key=''){

		$this->create();
		$this->save(array('status'=>$status, 'sp'=>$sp, 'api_errcode'=>$api_errcode, 'p_id'=>$p_id, 'content'=>$content, 'ip'=>getIp(), 'key'=>$key));
	}
}
?>