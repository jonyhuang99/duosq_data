<?php
//DAL:报警模块
namespace DAL;

class Alarm extends _Dal {

	//自动导入订单报警
	function importOrders($entry, $params=array()){

		if(date('H') > 8){
			$expire = HOUR*1.5;
		}else{
			$expire = HOUR*9;
		}
		$entry_params = D()->redis('alarm')->accum('auto_import:order', $expire, $entry);

		if($entry_params){
			$this->_fire($entry_params, $params, 101);
		}
	}

	//导入订单出错紧急报警
	function importOrdersErr($type){

		if(date('Y-m-01') == date('Y-m-d')){
			//每月1号，yiqifa数据为空，导致误报，此时延迟6小时报警
			$expire = HOUR * 6;
		}else{
			$expire = MINUTE*5;
		}
		$entry_params = D()->redis('alarm')->accum('auto_import:error', MINUTE*5, $type);

		if($entry_params){
			$this->_fire($entry_params, array(), 102);
		}
	}

	//保护模块产生的报警
	function protect($type, $entry, $params=array()){

		$entry_params = D()->redis('alarm')->accum('protect:'.$type, HOUR, $entry);

		if($entry_params){
			$max = 0;
			foreach ($entry_params as $key => $value) {
				$max = max($max, $value);
			}
			if($max < 4){
				//太小的监控值继续累积
				D()->redis('alarm')->accum('protect:'.$type, HOUR, $entry_params);
				return;
			}

			$params['type'] = $type;
			$this->_fire($entry_params, $params, 100);
		}
	}

	//接口调用异常报警
	function api($entry){

		$entry_params = D()->redis('alarm')->accum('api', MINUTE*5, $entry);

		if($entry_params){
			$max = 0;
			foreach ($entry_params as $key => $value) {
				$max = max($max, $value);
			}
			if($max < 4){
				//太小的监控值继续累积
				D()->redis('alarm')->accum('api', MINUTE*5, $entry_params);
				return;
			}

			$this->_fire($entry_params, array(), 103);
		}
	}

	//发出监控报警
	private function _fire($entry_params, $params, $sms_tpl=''){

		$content = array();
		ksort($entry_params);
		foreach($entry_params as $k => $v){
			$content[] = "{$k}:{$v}";
		}

		$default_p = array();
		$default_p['__time__'] = date('H:i');
		$default_p['__content__'] = join(',', $content);
		$params = array_merge($default_p, (array)$params);
		$ret = sendSms(C('comm', 'sms_monitor'), $sms_tpl, $params, 'alarm');
	}
}
?>