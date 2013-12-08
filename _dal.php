<?php
namespace DAL;
/**
 * 数据访问层基类，提供抽象业务数据模块逻辑
 */
class _Dal extends \Object {

	//返回db操作对象(表对象, 基于cakephp数据库访问底层)
	function db($table, $must_exist = false){

		static $_loaded = array();
		if(isset($_loaded[$table]))return $_loaded[$table];

		$obj_name = \inflector::camelize($table);

		if(@include "data/db/{$table}.php"){
			$obj_name = "\\DB\\{$obj_name}";

			$_loaded[$table] = new $obj_name;
			var_dump($_loaded[$table]);
		}else{

			if($must_exist)return false;
			//表操作类不存在，生成默认对象
			$_loaded[$table] = new \Model(false, \inflector::tableize($table));
			$_loaded[$table]->name = $obj_name;
		}
		return $_loaded[$table];
	}

	//返回redis操作对象(key对象)
	function redis($name){

	}

	//返回queue操作对象(queue对象)
	function queue($name){

	}

	//返回apc操作对象
	function apc($name){

	}

	//返回api操作对象(第三方API操作对象)
	function api($name){

	}
}

?>