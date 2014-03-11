<?php
//各类报警发送记录底层

namespace REDIS;

class Alarm extends _Redis {

	protected $namespace = 'alarm';

	/**
	 * 累计监控条目，释放达到时间，返回释放信号
	 * @param  string  $key       合并的业务
	 * @param  integer $duration  累计时长
	 * @param  string  $entry     叠加的条目(用,隔开多个)
	 * @return false/array        false:累计中  array:释放报警
	 */
	function accum($key='', $duration=3600, $entry='default'){

		$cache = $this->hgetall($key);

		$all_entry = $this->_merge($cache, $entry);

		if(!$cache){
			$all_entry['expire_on'] = time() + $duration;
		}

		//累计时长达到，释放内容
		if(@$cache['expire_on'] && $cache['expire_on'] < time()){

			$this->del($key);
			unset($all_entry['expire_on']);
			return $all_entry;
		}else{

			//继续累计
			foreach($all_entry as $single=>$count){
				$this->hset($key, $single, $count);
			}
		}

		return false;
	}

	//进行合并条目
	private function _merge($now, $entry){
		if(!$entry){
			return $now;
		}
		if(!$now)$now = array();
		if(!is_array($entry)){
			$arr = explode(',',$entry);
		}else{
			$arr = $entry;
		}

		foreach($arr as $a){
			$step = 1;
			if(strpos($a, ':')){
				list($a, $step) = explode(':', $a);
			}
			$now[$a] = @$now[$a] + $step;
		}
		return $now;
	}
}
?>