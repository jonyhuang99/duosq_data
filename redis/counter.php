<?php
//计数器专用底层
namespace REDIS;

class Counter extends _Redis {

	protected $namespace = 'counter';
	protected $dsn_type = 'database';

	/**
	 * 累计数值
	 * @return [type] [description]
	 */
	function sincr($tag, $expire=DAY, $date=''){

		if(!$tag)return;
		if($date) $date = ':'.$date;
		$key = "{$tag}".$date;
		$times = $this->incr($key);
		$this->expire($key, $expire);
		return $times;
	}

	/**
	 * 获取数值
	 * @param  [type] $tag    [description]
	 * @return [type]         [description]
	 */
	function sget($tag, $date=''){

		if(!$tag)return;
		if($date) $date = ':'.$date;
		$key = "{$tag}".$date;
		$times = $this->get($key);
		return $times;
	}

	/**
	 * 统计IP数量
	 * @return [type] [description]
	 */
	function ipadd($tag, $expire=DAY, $date=''){

		if(!$tag)return;
		if($date) $date = ':'.$date;
		$key = "{$tag}".$date;
		$ret = $this->sadd($key, getIp());
		$this->expire($key, $expire);
		return $ret;
	}

	/**
	 * 获取ip总数
	 * @param  [type] $tag    [description]
	 * @return [type]         [description]
	 */
	function ipcount($tag, $date=''){

		if(!$tag)return;
		if($date) $date = ':'.$date;
		$key = "{$tag}".$date;
		$times = $this->scard($key);
		return $times;
	}

	/**
	 * 剔除ip
	 * @param  [type] $tag    [description]
	 * @return [type]         [description]
	 */
	function ipdel($tag, $date=''){

		if(!$tag)return;
		if($date) $date = ':'.$date;
		$key = "{$tag}".$date;
		$ip = $this->spop($key);
		if($ip && !$this->sismember($key.':poped', $ip)){
			$this->sadd($key.':poped', $ip);
			$this->expire($key.':poped', MONTH);
			return $ip;
		}else{
			return false;
		}
	}
}
?>