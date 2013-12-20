<?php
//DAL:Redis访问层

namespace REDIS;

class _Redis extends \Object {

	protected $redis;

	/**
     * 析构方法，初始化redis
     */
    public function __construct() {
    	static $connected;

    	if(!$connected){

    		list($host, $port) = explode(':', C('REDIS_SESSION'));
	        $obj = new Redis();
	        $obj->connect($host, $port, 2);
	        $connected = $obj;
    	}

    	$this->redis = $connected;
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
	public function aheadUpdateLock($key, $expires, $lockExpires = 60, $aheadRate = 0.1) {
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
	public function getCacheKeyConds($conds, $keys, $prefix = ':conds_') {
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
	public function getJson($key) {
		return json_decode($this->redis->get($key), true);
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
	public function setJson($key, $val, $exp = null) {
		if (is_null($exp)) {
			$ret = $this->redis->set($key, json_encode($val));
		}
		else {
			$ret = $this->redis->setex($key, $exp, json_encode($val));
		}
		return $ret;
	}

    /**
     * 重载lRange方法，默认$offset为0，将第二个参数改写为limit属性，默认为10
     * @param string 键值
     * @param int 起始偏移量
     * @param int 条数
     * @return array 数组
     */
    public function lRange($key, $offset, $limit) {
        $offset = (0 >= $offset) ? 0 : $offset;
        $limit = (0 >= $limit) ? 10 : ($offset + $limit - 1);
        return $this->redis->lRange($key, $offset, $limit);
    }

    /**
     * 重载zRevRange方法，默认$offset为0，将第二个参数改写为limit属性，默认为10
     * @param string 键值
     * @param int 起始偏移量
     * @param int 条数
     * @return array 数组
     */
    public function zRange($key, $offset, $limit) {
        $offset = (0 >= $offset) ? 0 : $offset;
        $limit = (0 >= $limit) ? 10 : ($offset + $limit - 1);
        return $this->redis->zRevRange($key, $offset, $limit);
    }

    //将操作方法重定向到redis对象
    public function __call($method, $arg_array) {
        return call_user_func_array(array($this->redis, $method), $arg_array);
    }

}

?>