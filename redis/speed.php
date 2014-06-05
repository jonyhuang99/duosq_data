<?php
//速度限制专用底层
namespace REDIS;

class Speed extends _Redis {

	protected $namespace = 'speed';

	/**
	 * 判断当前速度是否安全
	 * @param  [type]  $obj    [description]
	 * @param  [type]  $expire [description]
	 * @param  [type]  $limit  [description]
	 * @param  boolean $wait   同步阻塞至安全为止
	 * @return boolean         [description]
	 */
	function isSafe($obj, $expire, $limit, $wait=true) {

		if(!$obj || !$expire || !$limit)return;
		$key = "expire:{$expire}:limit:{$limit}:obj:{$obj}";
		$count = $this->get($key);

		if(@$count && $count >= $limit){

			if(!$wait){
				return false;
			}else{

				$ttl = $this->ttl($key);
				sleep($ttl+1);
				$count = $this->incr($key);
				$this->expire($key, $expire);
				return $count;
			}
		}else{

			if($this->exists($key)){
				$count = $this->incr($key);
			}else{
				$count = $this->incr($key);
				$this->expire($key, $expire);
			}
			return $count;
		}
	}

	/**
	 * 单独按指定频率进行累计(带延迟惩罚)
	 * @return [type] [description]
	 */
	function sincr($obj, $expire, $limit){

		if(!$obj || !$expire || !$limit)return;
		$key = "expire:{$expire}:limit:{$limit}:obj:{$obj}";

		$times = $this->incr($key);

		if (1 == $times) { //次数超过1次，封锁指定时间
			$this->expire($key, $expire);
		}

		if ($times > $limit) { //超过限制，进行延迟
			$this->expire($key, $expire*1.5);
			return $times;
		}

		return false;
	}

	/**
	 * 单独获取是否超限
	 * @param  [type] $obj    [description]
	 * @param  [type] $expire [description]
	 * @param  [type] $limit  [description]
	 * @return [type]         [description]
	 */
	function sget($obj, $expire, $limit=1){

		if(!$obj || !$expire || !$limit)return;
		$key = "expire:{$expire}:limit:{$limit}:obj:{$obj}";
		$times = $this->get($key);
		if($times > $limit)
			return true;
	}
}
?>