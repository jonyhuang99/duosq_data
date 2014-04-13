<?php
//脚本专用redis连接，无限执行时间

namespace REDIS;

class Cache extends _Redis {

	protected $namespace = 'cache';
}
?>