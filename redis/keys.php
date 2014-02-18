<?php
//各类存于redis的临时KEY管理底层

namespace REDIS;

class Keys extends _Redis {

	protected $namespace = 'keys';
	protected $dsn_type = 'database';

	/**
	 * 多多集分宝打款key，12小时过期，value为空表示获取数据
	 * @param  string  $new_value 新的key值
	 * @param  integer $expire    过期时间
	 * @return [type]             array()
	 */
	function duoduo($new_value='',$expire=43200){

		if($new_value){
			return $this->set('duoduo:jfb', $new_value, $expire);
		}else{
			$value = $this->get('duoduo:jfb');
			$ttl = $this->ttl('duoduo:jfb');
			if(!$value){
				return array('value'=>'');
			}else{
				return array('value'=>$value, 'ttl'=>$ttl);
			}
		}
	}
}
?>