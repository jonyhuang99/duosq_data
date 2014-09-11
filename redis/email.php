<?php
//各类存于redis的临时KEY管理底层
namespace REDIS;

class Email extends _Redis {

	protected $namespace = 'email';
	protected $dsn_type = 'database';

	//增加一个被过滤的Email
	function addFilter($email, $type='ip:shanghai'){

		return $this->hset('filter:'.$type, $email, 1);
	}

	//检查Email是否被过滤了
	function checkFilter($email, $type='ip:shanghai'){

		return $this->hexists('filter:'.$type, $email);
	}
}
?>