<?php
//DAL:报警模块
namespace DAL;

class Alarm extends _Dal {

	//自动导入订单报警
	function importOrders($type, $entry, $params=array()){

		$entry_params = D()->redis('alarm')->accum('auto_import:'.$type.':order', HOUR*3, $entry);

		if($entry_params){
			$params['type'] = $type;
			$this->_fire($entry_params, $params, 101);
		}
	}

	//导入订单出错紧急报警
	function importOrdersErr($msg){
		$this->_fire(array(), array('msg'=>$msg), 102);
	}

	//保护模块产生的报警
	function protect($type, $entry, $params=array()){

		$entry_params = D()->redis('alarm')->accum('protect:'.$type, HOUR, $entry);

		if($entry_params){
			$params['type'] = $type;
			$this->_fire($entry_params, $params, 100);
		}
	}

	private function _fire($entry_params, $params, $sms_tpl=''){

		$content = array();
		foreach($entry_params as $k => $v){
			$content[] = "{$k}:{$v}";
		}

		$default_p = array();
		$default_p['__time__'] = date('H:i:s');
		$default_p['__content__'] = join(',',$content);
		$params = array_merge($default_p, $params);
		$ret = sendSms(C('comm', 'sms_monitor'), $sms_tpl, $params, 'alarm');
	}
}
?>