<?php
//DAL:Redis访问层
//redis方法请保持驼峰的命名规则，key值一律用小写&下划线
//必须指定namespace命名空间
//默认DSN为被动缓存，即有可能丢失数据，如果数据不能缺失，请指定dsn_type='database'
//注意：缓存模式的redis使用时，必须指定expire时间
//BUG：使用get_memory_use函数后，影响内存，会令redis操作抛异常

namespace REDIS;

class _Redis extends \Object {

	static $connected = array();

	protected $namespace; //当前模块key命名空间，***必填***，public以便创建空类赋值

	/**
	 * 重要纪要: 命令如果执行超时短于阻塞时间:
	 * 底层将抛出异常read error on connection，第二次调用，redis.so将不会发送任何命令，此时应重连
	 */

	//一般无需重载，当重载了exec_timeout，一旦连接单例已经存在，则当前exec_timeout不会有效
	protected $exec_timeout = 10;
	protected $dsn_type = 'cache'; //数据源类型(cache:不做dump数据操作，database:做AOF实时备份)
	protected $redis;
	protected $mcache = false; //启用本地内存缓存，单位秒
	protected $mcache_update = false; //是否马上更新本地缓存确保一致，写频繁的key启用会减低性能(不建议打开mcache)

	/**
	 * 析构方法，初始化redis
	 */
	function __construct() {

		$this->init();
	}

	//初始化redis连接
	protected function init(){

		$this->setDsn($this->dsn_type);

		if(!@self::$connected[$this->dsn]){

			list($host, $port, $db) = explode(':', $this->dsn);
			$obj = new \Redis();
			//当重载了exec_timeout，但连接单例已经存在，则exec_timeout不会有效
			$obj->connect($host, $port, $this->exec_timeout);
			if($db)$obj->select($db); //切换到指定数据库
			self::$connected[$this->dsn] = $obj;
		}
	}

	//重新恢复连接，此处一般用于script
	function reconnect(){

		list($host, $port, $db) = explode(':', $this->dsn);
		$this->close();
		$obj = new \Redis();
		$ret = $obj->connect($host, $port, $this->exec_timeout);
		if(!$ret)return false;
		if($db){
			$obj->select($db); //切换到指定数据库
		}
		self::$connected[$this->dsn] = $obj;
		return true;
	}

	//切换redis连接类型，返回redis模块对象
	function switchDsn($dsn_type){

		$this->setDsn($dsn_type);

		if(!@self::$connected[$this->dsn]){

			list($host, $port, $db) = explode(':', $this->dsn);
			$obj = new \Redis();

			$obj->connect($host, $port, $this->exec_timeout);
			if($db)$obj->select($db); //切换到指定数据库
			self::$connected[$this->dsn] = $obj;
		}

		return $this;
	}

	//根据使用类型设置DSN连接
	protected function setDsn($dsn_type){

		//TODO 未来此处可以根据namespace来路由不同的redis

		if($dsn_type == 'database'){
			$this->dsn = REDIS_DATABASE;
		}elseif($dsn_type == 'cache'){
			$this->dsn = REDIS_CACHE;
		}else{
			echo 'redis dsn must be "database" or "cache"';
			die();
		}
	}

	//重载select方法，防止强制加入命名空间
	function select($db){
		$this->redis()->select($db);
	}


	//主动切断连接
	function close(){
		$obj = $this->redis();
		if($obj){
			$obj->close();
			unset(self::$connected[$this->dsn]);
		}
	}

	//获取当前的redis操作对象
	protected function redis(){
		return self::$connected[$this->dsn];
	}

	/**
	 * 安全的预先更新锁
	 *
	 * @param string $key 要更新的key
	 * @param int $expires key的总有效期
	 * @param int $lockExpires 锁的有效期
	 * @param float $aheadRate 在{ttl<有效期*rate}时锁定并更新
	 * @return boolean
	 */
	function aheadUpdateLock($key, $expires, $lockExpires = 60, $aheadRate = 0.1) {
		$lockKey = "other:ahead_update_lock:$key";
		if ($this->ttl($key) < $expires * $aheadRate && !$this->get($lockKey)) {
			$this->set($lockKey, 1, $lockExpires);
			return true;
		}
		return false;
	}

	/**
	 * 将$conditions条件数组转化为RedisKey的一部分
	 *
	 * @param array $conds 筛选条件 支持一维/二维数组
	 * @param array $keys 使用部分条件
	 * @param string $prefix
	 * @return :conds_md5(conds) or ''
	 */
	function getCacheKeyConds($conds, $keys, $prefix = ':conds_') {
		$mask = array_fill_keys($keys, null);
		$conds = array_intersect_key($conds, $mask);

		if ($conds) {
			ksort($conds);
			foreach ($conds as $k => & $cond) {
				if (is_array($cond)) {
					sort($cond);
					$cond = implode(',', $cond);
				}
				$cond = $k . '=' . (string) $cond;
			}
			return $prefix . md5(implode('&', $conds));
		}
		return '';
	}

	/**
	 * Redis->get(key) and json_decode
	 *
	 * @param string $key
	 * @return mixed
	 */
	function getArray($key) {
		return unserialize($this->get($key));
	}

	/**
	 * 设置Redis键
	 *
	 * 用于安全更新Redis，如果值有效则设置否则删除
	 *
	 * @param string $key
	 * @param mixed $val
	 * @param int $exp
	 * @return boolean
	 */
	function setArray($key, $val, $exp = null) {
		if (is_null($exp)) {
			$ret = $this->set($key, serialize($val));
		}
		else {
			$ret = $this->setex($key, $exp, serialize($val));
		}
		return $ret;
	}

	/**
	 * 重载lrange方法，默认$offset为0，将第二个参数改写为limit属性，默认为10
	 * @param string 键值
	 * @param int 起始偏移量
	 * @param int 条数
	 * @return array 数组
	 */
	function lrange($key, $offset, $limit) {
		$offset = (0 >= $offset) ? 0 : $offset;
		$limit = (0 >= $limit) ? 10 : ($offset + $limit - 1);
		return $this->redis()->lRange($key, $offset, $limit);
	}

	/**
	 * 重载zRevRange方法，默认$offset为0，将第二个参数改写为limit属性，默认为10
	 * @param string 键值
	 * @param int 起始偏移量
	 * @param int 条数
	 * @return array 数组
	 */
	function zrange($key, $offset, $limit) {
		$offset = (0 >= $offset) ? 0 : $offset;
		$limit = (0 >= $limit) ? 10 : ($offset + $limit - 1);
		return $this->redis()->zRevRange($key, $offset, $limit);
	}

	//重载brpoplpush，第二参数加上namespace
	function brpoplpush($source, $dst, $timeout=0){
		return $this->redis()->brpoplpush($this->namespace.':'.$source, $this->namespace.':'.$dst, $timeout);
	}

	//将操作方法重定向到redis对象
	function __call($method, $arg_array=null) {

		if($arg_array){
			//对key的访问只允许继承类，而D()->redis()形式返回的对象，需要赋值namespace
			if(!$this->namespace){
				echo 'redis namespace miss';
				die();
			}
			//此处为了提升性能，没判断命令类型，强制加入命名空间，如果有冲突的命令，请用重载的方式避免
			$arg_array[0] = $this->namespace . ':' . $arg_array[0];
		}

		if($this->mcache && D()->mcache()->enable() && !DEBUG){
			$low_m = strtolower($method);
			$all_cache_m = array('get','hget','hgetall','hmget','smembers');
			$key_md5 = md5($arg_array[0]);
			$aKey = $method.':'.$key_md5.':'.md5(serialize($arg_array));
			if(in_array($low_m, $all_cache_m)){

				$succ = false;
				$ret = apc_fetch($aKey, $succ);
				//缓存包括非的结果
				if ($succ === false) {
					//file_put_contents('/tmp/redis.log', date('[H:i:s]').$method.json_encode($arg_array)."\n\n", 8);
					$ret = call_user_func_array(array($this->redis(), $method), $arg_array);
					if($ret)D()->mcache()->set($aKey, $ret, $this->mcache);
				}
				return $ret;
			}else{

				//file_put_contents('/tmp/redis.log', date('[H:i:s]').$method.json_encode($arg_array)."\n\n", 8);
				$ret = call_user_func_array(array($this->redis(), $method), $arg_array);
				if($this->mcache_update){
					$cachedKeys = new \APCIterator('user', '/'.$key_md5.'/', APC_ITER_VALUE);
					D()->mcache()->delete($cachedKeys);
				}
			}
		}else{
			//file_put_contents('/tmp/redis.log', date('[H:i:s]').$method.json_encode($arg_array)."\n\n", 8);
			$ret = call_user_func_array(array($this->redis(), $method), $arg_array);
		}

		return $ret;
	}

}

?>