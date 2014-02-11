<?php
//DAL:速控限制模块
namespace DAL;

class Speed extends _Dal {

	function email($emails){

		if(!$emails)return false;

		$rule = array(
			'qq.com'=>array('second'=>1,'minute'=>20,'hour'=>500),
			'163.com'=>array('second'=>2,'minute'=>20,'hour'=>500),
			'126.com'=>array('second'=>2,'minute'=>20,'hour'=>500),
			'gmail.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'hotmail.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'live.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'yahoo.com.cn'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'yahoo.cn'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'sina.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'sohu.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'139.com'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'yeah.net'=>array('second'=>2,'minute'=>100,'hour'=>5000),
			'other'=>array('second'=>4,'minute'=>200,'hour'=>12000)
		);

		foreach($emails as $email){

			$ext = low(array_pop(explode('@', $email)));

			if(isset($rule[$ext])){
				$limit = $rule[$ext];
			}else{
				$limit = $rule['other'];
			}

			$this->redis('speed')->isSafe('email:'.$ext, 1, $limit['second']);
			$this->redis('speed')->isSafe('email:'.$ext, 60, $limit['minute']);
			$this->redis('speed')->isSafe('email:'.$ext, 3600, $limit['hour']);
		}

		return true;
	}
}
?>