<?php
//数据访问层基类，提供抽象业务数据模块逻辑

namespace DAL;

class _Dal extends \Object {

	/**
	 * 返回db操作对象(表对象, 基于cakephp数据库访问底层)
	 * @param  [string]  $m          [db目录模块名字]
	 * @param  boolean $must_exist [是否允许不存在模块，自动创建同名表操作模块]
	 * @return [obj]              [cakephp model操作对象]
	 */
	function db($m, $must_exist = false){

		static $_loaded = array();
		if(isset($_loaded[$m]))return $_loaded[$m];

		$obj_name = \inflector::camelize($m);
		require_once '_db.php';

		if(include "data/db/{$m}.php"){

			$obj_name = "\\DB\\{$obj_name}";
			$_loaded[$m] = new $obj_name;

		}else{

			if($must_exist)return false;
			//表操作类不存在，生成默认对象
			$_loaded[$m] = new \Model(false, \inflector::tableize($m));
			$_loaded[$m]->name = $obj_name;
		}
		return $_loaded[$m];
	}

	//返回redis操作对象(key对象)
	function redis($m){

	}

	//返回queue操作对象(queue对象)
	function queue($m){

		static $_loaded = null;
		if(!$_loaded){
			require '_queue.php';
			$_loaded = new \QUEUE\_Queue;
		}
		return $_loaded;
	}

	//返回xcache操作对象
	function xcache(){

		static $_loaded = null;
		if(!$_loaded){
			require '_session.php';
			$_loaded = new \SESSION\_Session;
		}
		return $_loaded;
	}

	//返回api操作对象(第三方API操作对象)
	function api($m){

	}

	//使用lazy session对象操作session
	function sess($key=null, $value=null){

		static $_loaded = null;
		if(!$_loaded){
			require '_session.php';
			$_loaded = new \Session\_Session;
		}
		if($key == null){
			return $_loaded;
		}else{
			if($value!==null){
				$_loaded->{$key} = $value;
			}else{
				return $_loaded->{$key};
			}
		}
	}
}

?>