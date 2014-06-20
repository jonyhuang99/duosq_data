<?php
//缓存用redis

namespace REDIS;

class Cache extends _Redis {

	protected $namespace = 'cache';
	protected $mcache = 60;
}
?>