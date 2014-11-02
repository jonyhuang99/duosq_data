<?php
//DAL:报警模块
namespace DAL;

class Alarm extends _Dal {

	//自动导入订单报警
	function importOrders($entry){

		if(date('H') > 8){
			$expire = HOUR*3;
		}else{
			$expire = HOUR*9;
		}
		$entry_params = D()->redis('alarm')->accum('auto_import:order', $expire, $entry);

		if($entry_params){
			$this->_fireEmail('订单跟单统计:正常', $entry_params);
		}
	}

	//导入订单出错紧急报警
	function importOrdersErr($type){

		if(date('d') == 1 || date('H') < 12){
			//每月1号，yiqifa数据为空，导致误报，此时延迟6小时报警
			$expire = HOUR*9;
		}else{
			$expire = HOUR*3;
		}
		$entry_params = D()->redis('alarm')->accum('auto_import:error', $expire, $type);

		if($entry_params){
			$this->_fireEmail('订单跟单统计:出错', $entry_params);
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
			$this->_fireSms($entry_params, $params, 100);
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

			$this->_fireSms($entry_params, array(), 103);
		}
	}

	//多多key接近过期
	function duoduo($expire){

		$this->_fireEmail('duduo支付key有效期监控', array('duoduo_key'=>'还剩'.$expire.'小时过期'));
	}

	//特卖：自动导入返利网订单报警
	function promoImportFanliOrder($entry){

		$entry_params = D()->redis('alarm')->accum('promo:auto_import:fanli_orders', HOUR*6, $entry);

		if($entry_params){
			$this->_fireEmail('特卖：返利网订单导入', $entry_params);
		}
	}

	//特卖：自动导入没得比数据报警
	function promoImportMeidebiData($entry){

		$entry_params = D()->redis('alarm')->accum('promo:auto_import:meidebi_data', HOUR*6, $entry);

		if($entry_params){
			$this->_fireEmail('特卖：没得比数据导入', $entry_params);
		}
	}

	//特卖：降价数据新增报警
	function promoDetectDiscountData($entry){

		$entry_params = D()->redis('alarm')->accum('promo:detect:discount', HOUR*6, $entry);

		if($entry_params){
			$this->_fireEmail('特卖：降价数据新增', $entry_params);
		}
	}

	//订阅：IP邮箱订阅超限新增报警
	function subscribe($entry){

		$entry_params = D()->redis('alarm')->accum('subscribe:ip', HOUR, $entry);

		if($entry_params){
			$this->_fireEmail('订阅：IP尝试超限', $entry_params);
		}
	}

	//监控daemon进程
	function processMonitor($process){

		if(!$process)return;
		$this->_fireEmail('进程监控报警', array('进程不存在'=>join(',', $process)));
	}

	//发出监控报警
	private function _fireSms($entry_params, $params, $sms_tpl=''){

		$content = array();
		ksort($entry_params);
		foreach($entry_params as $k => $v){
			$content[] = "{$k}:{$v}";
		}

		$default_p = array();
		$default_p['__time__'] = date('H:i');
		$default_p['__content__'] = join(',', $content);
		$params = array_merge($default_p, (array)$params);
		$ret = sendSms(C('comm', 'monitor_sms'), $sms_tpl, $params, 'alarm');
	}

	private function _fireEmail($title, $entry_params=array()){

		$param = array();
		$param['title'] = $title;

		$content = array();
		foreach($entry_params as $k => $v){
			$content[] = "{$k}:{$v}";
		}
		$param['content'] = join(',', $content);
		$param['time'] = date('H:i');
		$ret =sendMail(C('comm', 'monitor_email'), $param, 'sys_alarm', $msg);

	}
}
?>