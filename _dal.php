<?php
//数据访问层基类，提供抽象业务数据模块逻辑
namespace DAL;
class _Dal extends \Object {

	protected static $_loaded = array();
	/**
	 * 返回db操作对象(表对象, 基于cakephp数据库访问底层)
	 * @param  [string]  $m          db目录模块名字，为空时返回默认数据库操作对象
	 * @param  boolean $must_exist   是否允许不存在模块，自动创建同名表操作模块
	 * @return [obj]                 cakephp model操作对象
	 *
	 * ex. $this->db('table')->save($data)	//操作cakephp model对象
	 * ex. $this->db('table', true)->save($data)	//table.php必须存在db目录，否则返回空
	 * ex. $this->db()->query('sql');	//返回默认对象
	 */
	function db($m = '', $must_exist = false) {

		if (!$m) $m = 'empty'; //表操作类不存在，生成默认对象

		$m_key = '_db_' . $m;
		if (isset(self::$_loaded[$m_key])) return self::$_loaded[$m_key];
		$table = array_pop(explode('.', $m)); //使用.来区分目录
		$obj_name = \inflector::camelize($table);
		require_once '_db.php';

		if ($m && @include "data/db/".r('.','/',$m).".php") {
			$obj_name = "\\DB\\{$obj_name}";
			self::$_loaded[$m_key] = new $obj_name;
		} else {
			if ($must_exist) return false;
			self::$_loaded[$m_key] = new \Model(false, $table);
			self::$_loaded[$m_key]->name = $obj_name;
			self::$_loaded[$m_key]->className = $obj_name;
		}
		return self::$_loaded[$m_key];
	}

	/**
	 * 返回redis操作对象(key对象)
	 * @param  string $m redis模块名称
	 * @return object    redis操作对象
	 */
	function redis($m='') {

		if (!$m) return false;
		$m_key = '_redis_' . $m;

		if (isset(self::$_loaded[$m_key])) return self::$_loaded[$m_key];
		$obj_name = \inflector::camelize($m);
		require_once '_redis.php';

		if (include_once "data/redis/{$m}.php") {

			$obj_name = "\\REDIS\\{$obj_name}";
			self::$_loaded[$m_key] = new $obj_name;
		}else{
			return false;
		}
		return self::$_loaded[$m_key];
	}

	//返回queue操作对象(queue对象)
	function queue($m) {

	}

	//返回xcache操作对象
	function xcache() {

		$m_key = '_xcache_';
		if(isset(self::$_loaded[$m_key]))return self::$_loaded[$m_key];
		require_once '_xcache.php';
		self::$_loaded[$m_key] = new \XCACHE\_Xcache;
		return self::$_loaded[$m_key];
	}

	//返回api操作对象(第三方API操作对象)
	function api($m) {

		if (!$m) return;
		$m_key = '_api_' . $m;

		if (isset(self::$_loaded[$m_key])) return self::$_loaded[$m_key];
		$obj_name = \inflector::camelize($m);
		require_once '_api.php';

		if (include "data/api/{$m}.php") {

			$obj_name = "\\API\\{$obj_name}";
			self::$_loaded[$m_key] = new $obj_name;
		} else {
			return;
		}
		return self::$_loaded[$m_key];
	}

	/**
	 * 使用lazy session对象操作session，$key为空时返回session对象
	 * @param  string $key   要操作的键
	 * @param  mix    $value 要写入的值(值为null时表示GET模式)
	 * @return mix           GET模式，返回具体值
	 *
	 * ex.   $this->sess('key', 'value'); //设置
	 * ex.   $this->sess('key.v1', 'value'); //设置多维
	 * ex.   $this->sess('key');	//获取
	 * ex.   $this->sess('key', null); //删除key
	 * ex.   $this->sess('key', ''); //设置key为''
	 * ex.   $this->sess(); //返回session对象
	 */
	function sess($key = null, $value = '__get_my_key__') {

		$m_key = '_session_';

		if (!isset(self::$_loaded[$m_key])) {
			require '_session.php';
			self::$_loaded[$m_key] = new \Session\_Session;
		}

		if (!$key) {
			return self::$_loaded[$m_key];
		} else {
			if ($value !==null && $value!='__get_my_key__') {
				self::$_loaded[$m_key]->{$key} = $value;
			} else if($value === null) {
				return self::$_loaded[$m_key]->delete($key);
			} else if($value == '__get_my_key__'){
				return self::$_loaded[$m_key]->{$key};
			}
		}
	}

	//情况缓存的对象
	function clear($obj_name=''){
		if($obj_name && isset(self::$_loaded[$obj_name])){
			unset(self::$_loaded[$obj_name]);
			return;
		}

		if(!$obj_name){
			self::$_loaded = array();
		}
	}

	//快捷方法，返回表对应的带命名空间的DB操作类名
	protected function _table2class($table_name){
		$sub_class_name = \inflector::camelize($table_name);
		$sub_class_name = "\\DB\\{$sub_class_name}";
		return $sub_class_name;
	}
}
?>