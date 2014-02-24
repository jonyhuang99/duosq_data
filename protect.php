<?php
//DAL:安全防守模块
namespace DAL;

class Protect extends _Dal {

	//判断注册攻击，保护注册模块
	function attackReg(){

		$same_ip_c = date('Y-m-d', time() - 86400); //相同的注册IP_C时间区间
		$same_agent  = date('Y-m-d H:i:s', time() - 120); //相同的客户端时间区间
		$same_area = date('Y-m-d H:i:s', time() - 120);//相同的地区区间

		$my_id = D('myuser')->getId();
		if(!$my_id)return true;

		//$ip_c = getIpByLevel('c');
		$ip = getIpByLevel('c');
		$user_ids = array();
		$count_ip = array();
		$count_utmo = array();
		$count_agent = array();
		$count_area = array();
		$action_code = 100; //恶意注册拦截

		//IP C段相同
		$ret = $this->db('user')->findAll(array('id'=>"<> {$my_id}",'reg_ip'=>"like {$ip}%",'createdate'=>"> {$same_ip_c}"));
		$count_ip = fieldSet($ret, 'id');
		if($count_ip){
			D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'ip', 'data2'=>$ip, 'data4'=>join(',',$count_ip)));
		}

		//utmo重复注册
		$utmo = D('track')->get();
		if($utmo){
			$ret = $this->db('user')->findAll(array('id'=>"<> {$my_id}", 'utmo'=>$utmo));
			$count_utmo = fieldSet($ret, 'id');
			if($count_utmo){
				D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'utmo', 'data2'=>$utmo, 'data4'=>join(',',$count_utmo)));
			}
		}

		//客户端相同
		$agent = getAgent();
		if(strlen($agent) > 80){
			$ret = $this->db('user')->findAll(array('id'=>"<> {$my_id}",'agent'=>"{$agent}",'createtime'=>"> {$same_agent}"));
			$count_agent = fieldSet($ret, 'id');
			//进一步放行客户端
			if(count($count_agent) < 6) $count_agent = array();
			if($count_agent){
				D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'agent', 'data2'=>$agent, 'data4'=>join(',',$count_agent)));
			}
		}

		//地区相同
		$area = getAreaByIp();
		if(mb_strlen($area, 'utf8')>4){
			$ret = $this->db('user')->findAll(array('id'=>"<> {$my_id}",'reg_area_detail'=>"{$area}",'createtime'=>"> {$same_area}"));
			$count_area = fieldSet($ret, 'id');

			if($count_area){
				D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'area', 'data2'=>$area, 'data4'=>join(',',$count_area)));
			}
		}

		$user_ids = array_unique(array_merge($count_ip, $count_utmo, $count_agent, $count_area));

		if($user_ids){

			$this->alarm('reg');

			if(count($user_ids) < 4){
				//本人加入1级黑名单(购物打折，给上游提成减少)
				if($my_id)D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_1);
			}else{
				//恶意用户，有关的用户全部加入2级黑名单(不新增任何购物收入)
				//if($my_id)$user_ids[] = $my_id;
				D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_2);
			}

			return true;
		}else{

			//如果父亲是深度黑名单
			$parent_id = getParentId();
			$parent_status = D('user')->getStatus($parent_id);

			if($parent_id && $parent_status == \DAL\User::STATUS_BLACK_2){
				D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_1);

				D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'parent', 'data2'=>$parent_id));

				$this->alarm('reg');
				return true;
			}
		}

		return false;
	}

	//统一发送报警
	private function alarm($type){

		if($type == 'reg'){
			$sent = $this->redis('alarm')->sent('register:protected', 120);
			if(!$sent){
				sendSms(C('comm', 'sms_monitor'), 100, array('time'=>date('m-d H:i:s'), 'ip'=>getIp(), 'area'=>getAreaByIp(), 'alipay'=>D('myuser')->getAlipay()));
			}
		}
	}
}
?>