<?php
namespace DB;

class Demo extends Model {

	var $name = 'Demo';
	var $useTable = 'table'; //指定表名，否则为$name
	var $primaryKey = 'id'; //指定主键

	var $useDbConfig = 'default'; //指定数据库DSN
}

?>