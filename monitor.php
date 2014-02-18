<?php
//DAL:监控报警模块
namespace DAL;

class Monitor extends _Dal {

	//恶意注册，报警
	function register(){

		$sent = $this->redis('monitor')->sent('register:ip:'.getIp());
		if(!$sent){
			sendSms(C('comm', 'sms_monitor'), 100, array('ip'=>getIp(), 'area'=>getAreaByIp(), 'alipay'=>D('myuser')->getAlipay()));
		}
	}
}
?>