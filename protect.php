<?php
//DAL:安全防守模块
namespace DAL;

class Protect extends _Dal {

	//判断注册攻击，保护注册模块
	function attackReg(){

		$attack = false;
		$same_ip_c = date('Y-m-d', time() - DAY*7); //相同的注册IP_C时间区间
		$same_agent  = date('Y-m-d H:i:s', time() - 30); //相同的客户端时间区间
		$same_area = date('Y-m-d H:i:s', time() - 120);//相同的地区区间
		$same_day =  date('Y-m-d H:i:s', time() - DAY); //当天
		$same_hour =  date('Y-m-d H:i:s', time() - HOUR); //上小时

		$my_id = D('myuser')->getId();
		$my_alipay = D('myuser')->getAlipay();
		if(!$my_id)return true;

		//$ip_c = getIpByLevel('c');
		$ip = getIp();
		$ip_c = getIpByLevel('c');
		$user_ids = array();
		$count_ip = array();
		$count_utmo = array();
		$count_agent = array();
		$count_area = array();
		$action_code = 100; //恶意注册拦截
		$entry = array();

		//utmo重复注册
		$utmo = D('track')->get();
		//客户端相同
		$agent = getAgent();
		//地区相同
		$area_detail = getAreaByIp('', 'detail');
		if(mb_strlen($area_detail, 'utf8')>4 && strpos($area_detail, '深圳市')===false){
			$area_limit = 3;
		}else{
			$area_limit = 5;
		}

		//严格识别重复领取，命中则该用户无红包领取权，用户防范正常用户输入多个账号
		$strict1 = $this->db('user')->find(array('id'=>"<> {$my_id}",'reg_ip'=>"like {$ip_c}%",'agent'=>$agent,'createdate'=>"> {$same_day}"));
		$strict2 = $this->db('user')->findAll(array('id'=>"<> {$my_id}",'reg_area_detail'=>$area_detail,'agent'=>$agent,'createdate'=>"> {$same_hour}"));
		if($strict1 || count($strict2)>$area_limit){
			D('user')->markUserCashgiftInvalid($my_id);
		}

		//IP C段相同
		$ret = $this->db('user')->findAll(array('id'=>"<> {$my_id}",'reg_ip'=>"like {$ip_c}%",'createdate'=>"> {$same_ip_c}"));
		$count_ip = fieldSet($ret, 'id');
		if($count_ip){
			D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'ip', 'data2'=>$ip_c, 'data4'=>join(',',$count_ip)));
			$entry[] = 'ip';
		}

		if($utmo){
			$ret = $this->db('user')->findAll(array('id'=>"<> {$my_id}", 'utmo'=>$utmo));
			$count_utmo = fieldSet($ret, 'id');
			if($count_utmo){
				D('user')->markUserCashgiftInvalid($my_id);
				D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'utmo', 'data2'=>$utmo, 'data4'=>join(',',$count_utmo)));
				$entry[] = 'utmo';
			}
		}

		if(stripos($my_alipay, 'eyou.com')!==false){
			D('user')->markUserCashgiftInvalid($my_id);
			D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'eyou', 'data2'=>$my_alipay, 'data4'=>join(',',$count_utmo)));
				$entry[] = 'eyou';
		}

		//严格识别完成

		if(strlen($agent) > 80){
			$ret = $this->db('user')->findAll(array('id'=>"<> {$my_id}",'agent'=>"{$agent}",'createtime'=>"> {$same_agent}"));
			$count_agent = fieldSet($ret, 'id');
			//进一步放行客户端
			if(count($count_agent) < 6) $count_agent = array();
			if($count_agent){
				D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'agent', 'data2'=>$agent, 'data4'=>join(',',$count_agent)));
				$entry[] = 'agent';
			}
		}

		if(mb_strlen($area_detail, 'utf8')>4){
			$ret = $this->db('user')->findAll(array('id'=>"<> {$my_id}",'reg_area_detail'=>$area_detail,'createtime'=>"> {$same_area}"));
			$count_area = fieldSet($ret, 'id');

			if($count_area){
				D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'area', 'data2'=>$area_detail, 'data4'=>join(',',$count_area)));
			}
		}

		$user_ids = array_unique(array_merge($count_ip, $count_utmo, $count_agent, $count_area));

		if($user_ids){

			if(count($user_ids) < 4){
				//本人加入1级黑名单(购物打折，给上游提成减少)
				if($my_id)D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_1);
				$this->alarm('reg', $entry);

			}else{
				//恶意用户，有关的用户全部加入2级黑名单(不新增任何购物收入)
				//if($my_id)$user_ids[] = $my_id;
				D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_2);
				$this->alarm('reg', $entry, true);
			}
			$attack = true;

		}else{

			//如果父亲是深度黑名单
			$parent_id = getCookieParentId();
			$parent_status = D('user')->getStatus($parent_id);

			if($parent_id && $parent_status == \DAL\User::STATUS_BLACK_2){
				D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_1);

				D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'parent', 'data2'=>$parent_id));

				$this->alarm('reg', array('parent'), true);
				$attack = true;
			}
		}

		//如果命中黑名单，直接深度黑名单
		if($this->db('black')->find(array('alipay'=>low($my_alipay))) || $this->redis('speed')->sget('send_cashgift:black_list:ip:'.$ip_c, DAY, 1)){

			D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_2);
			D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'black', 'data2'=>$my_alipay));
			$this->alarm('reg', array('black_list'), true);
			$attack = true;

			//只要该IP_C新增过黑名单，接下来1天，所有账号均进黑名单
			$times = $this->redis('speed')->sincr('send_cashgift:black_list:ip:'.$ip_c, DAY, 1);
			if($times > 1){
				$this->alarm('reg', array('black_ip'), true);
			}
		}

		//如果支付命中规则，直接深度黑名单
		if(preg_match('/tb[0-9\_]{5,}@163.com/i', $my_alipay) || preg_match('/16127.+?163\.com/i', $my_alipay)){

			D('user')->markBlack($my_id, \DAL\User::STATUS_BLACK_2);
			D('log')->action($action_code, 1, array('status'=>1, 'data1'=>'black', 'data2'=>$my_alipay));
			$this->alarm('reg', array('black_rule'), true);
			$attack = true;
		}

		return $attack;
	}

	//统一发送报警
	function alarm($type, $entry, $emergent=false){

		if($type == 'reg'){
			if($emergent){
				$need_release = $this->redis('alarm')->release('register:normal', 1800, join(',',$entry));
				$level = '普通';
			}else{
				$need_release = $this->redis('alarm')->release('register:attack', 1800, join(',',$entry));
				$level = '深度';
			}

			if($need_release){

				$content = array();
				foreach($need_release as $k => $v){
					$content[] = "{$k}:$v";
				}
				$params = array();
				$params['time'] = date('H:i:s');
				$params['level'] = $level;
				$params['content'] = join(',',$content);
				sendSms(C('comm', 'sms_monitor'), 100, $params, 'monitor');

				return true;
			}else{
				return false;
			}
		}
	}
}
?>