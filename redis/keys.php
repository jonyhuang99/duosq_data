<?php
//各类存于redis的临时KEY管理底层

namespace REDIS;

class Keys extends _Redis {

	var $namespace = 'keys';

	//多多集分宝打款key，12小时过期
	function duoduo($new_value='',$expire=43200){

		if($new_value){
			return $this->set('duoduo:jfb', $new_value, $expire);
		}else{
			$value = $this->get('duoduo:jfb');
			$ttl = $this->ttl('duoduo:jfb');
			return array('value'=>$value, 'ttl'=>$ttl);
		}
	}
}
?>