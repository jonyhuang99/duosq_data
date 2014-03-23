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

	/**
	 * 注册用户名命中黑名单
	 * @return [type] [description]
	 */
	function blacklist($mode = 'set'){

		if($mode == 'set'){
			return $this->redis('speed')->sincr('send_cashgift:black_list:ip:'.getIpByLevel('c'), DAY, 1);
		}else{
			return $this->redis('speed')->sget('send_cashgift:black_list:ip:'.$ip_c, DAY, 1);
		}
	}

	/**
	 * 控制新人获取礼包速度，是否超出
	 * @param  [type] $limit_type [description]
	 * @param  [type] $mobile     [description]
	 * @return [type]             [description]
	 */
	function cashgift($limit_type='ip', $phrase=''){

		if($limit_type == 'ip'){
			$limit = 3;
			return $this->redis('speed')->sincr('send_cashgift:ip_b:'.getIpByLevel('b'), HOUR*6, $limit);
		}

		if($limit_type == 'mobile'){
			if(!$phrase)return false;
			$phrase_pre = substr($phrase, 0, 7);
			$limit = 3;
			$ret = $this->redis('speed')->sincr('send_cashgift:mobile_pre:'.$phrase_pre, HOUR*24, $limit);
			if($ret){
				return $phrase_pre;
			}
		}

		if($limit_type == 'alipay_pre'){
			if(!$phrase)return false;
			$phrase_pre = substr($phrase, 0, 4);
			$limit = 5;
			$ret = $this->redis('speed')->sincr('send_cashgift:alipay_pre:'.$phrase_pre, HOUR*12, $limit);
			if($ret){
				return $phrase_pre;
			}
		}

		if($limit_type == 'agent'){
			$agent = getAgent();
			if(stripos($agent, '21.0.1180.89')!==false){
				return false;
			}

			if(stripos($agent, '30.0.1599')!==false){
				return false;
			}

			$area_detail = getAreaByIp('', 'detail');
			$limit = 3;
			$ret = $this->redis('speed')->sincr('send_cashgift:area:'.$area_detail.':agent:'.md5($agent), HOUR*4, $limit);
			if($ret){
				return array('area_detail'=>$area_detail, 'agent'=>$agent);
			}

			$limit = 20;
			$ret = $this->redis('speed')->sincr('send_cashgift:agent:'.md5($agent), HOUR, $limit);
			if($ret){
				return array('agent'=>$agent);
			}
		}

		if($limit_type == 'country'){
			if(!$phrase)return false;

			$area_detail = getAreaByIp('', 'detail');
			$ret = getProvince($area_detail);
			if(!$ret){
				return $area_detail;
			}
		}
	}

	/**
	 * 用户自助跟单速控
	 * @return [type] [description]
	 */
	function chUser($mode = 'set'){

		if($mode == 'set'){
			return $this->redis('speed')->sincr('ch_user:user_id:'.D('myuser')->getId(), DAY, 4);
		}else{
			return $this->redis('speed')->sget('ch_user:user_id:'.D('myuser')->getId(), DAY, 4);
		}
	}
}
?>