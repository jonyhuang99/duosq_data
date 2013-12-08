<?php
namespace DB;

class Demo extends Model {

	var $name = 'Demo';
	var $useTable = 'table'; //如果省略，数据库表名必须为标准英文复数
	var $primaryKey = 'id';

	var $useDbConfig = 'default';
}

?>