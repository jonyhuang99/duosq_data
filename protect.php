<?php
//DAL:安全防守模块
namespace DAL;

class Protect extends _Dal {

	//判断注册攻击，保护注册模块
	function attackReg(){

		//7天内有无相同的注册IP_C
		$day = date('Y-m-d', time() - 3*86400);
		$alipay = D('myuser')->getAlipay();
		$ip_c = getIpByLevel('c');
		$user_ids = array();
		$count_ip = array();
		$count_utmo = array();
		if($alipay){//跳过自己，防止任务集分宝误判
			$ret = $this->db('user')->findAll(array('reg_ip'=>"like {$ip_c}%", 'createdate'=>"> {$day}", 'alipay'=>"<> {$alipay}"));
			$count_ip = fieldSet($ret, 'id');

			$utmo = D('track')->get();
			if($utmo){
				$ret = $this->db('user')->findAll(array('utmo'=>$utmo, 'alipay'=>"<> {$alipay}"));
				$count_utmo = fieldSet($ret, 'id');
			}
			$user_ids = array_unique(array_merge($count_ip, $count_utmo));

		}else{
			$ret = $this->db('user')->findAll(array('reg_ip'=>"like {$ip_c}%", 'createdate'=>"> {$day}"));
			$count_ip = fieldSet($ret, 'id');

			$utmo = D('track')->get();
			if($utmo){
				$ret = $this->db('user')->findAll(array('utmo'=>$utmo));
				$count_utmo = fieldSet($ret, 'id');
			}
			$user_ids = array_unique(array_merge($count_ip, $count_utmo));
		}

		if($user_ids){

			$this->alarm('reg');
			$my_id = D('myuser')->getId();
			if(count($user_ids) < 4){
				//本人加入1级黑名单(购物打折，给上游提成减少)
				if($my_id)D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_1);
			}else{
				//恶意用户，有关的用户全部加入2级黑名单(不新增任何购物收入)
				if($my_id)$user_ids[] = $my_id;
				D('user')->markBlack($user_ids, \DAL\User::STATUS_BLACK_2);
			}

			return true;
		}

		return false;
	}

	//统一发送报警
	private function alarm($type){

		if($type == 'reg'){
			$sent = $this->redis('alarm')->sent('register:ip:'.getIp());
			if(!$sent){
				sendSms(C('comm', 'sms_monitor'), 100, array('ip'=>getIp(), 'area'=>getAreaByIp(), 'alipay'=>D('myuser')->getAlipay()));
			}
		}
	}
}
?>