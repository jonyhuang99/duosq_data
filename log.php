<?php
//DAL:日志类模块操作
namespace DAL;

class Log extends _Dal {

	/**
	 * 用户行为日志(表log_action)，支持自定义参数(数组入参)：
	 * op     : 行为码(见myconfig/code_act.php)    [必填]
	 * status : 行为状态(0:失败 1:成功 2:待审)       [必填]
	 * data1  : 自由使用字段1(100字符内，有索引)      [选填]
	 * data2  : 自由使用字段2(100字符内，有索引)      [选填]
	 * data3  : 自由使用字段3(100字符内，有索引)      [选填]
	 *
	 * data4  : 自由使用字段4(1000字符内，无索引)      [选填]
	 * data5  : 自由使用字段5(1000字符内，无索引)      [选填]
	 * operator : 操作者(0:user 1:system 2:admin)  [选填，默认1]
	 * operator_id : 操作者ID                      [选填，默认session取用户ID]
	 */
	function action($op, $status=1, $data=array()){

		if(!$op)return;

		if(!@$data['user_id']){
			if(@$_REQUEST['user_id']){
				$data['operator_id'] = $_REQUEST['user_id'];
			}else if($uid = D('myuser')->getId()){
				$data['operator_id'] = $uid;
			}
		}

		$data['op'] = $op;
		$data['status'] = $status;
		$data['ip'] = getIp();
		$data['ip_c'] = getIpByLevel('c');
		$data['area'] = getAreaByIp();
		$data['referer'] = env('HTTP_REFERER');
		$data['request'] = json_encode($_REQUEST);
		$data['utmo'] = @$_COOKIE['__utmo'];
		$data['client'] = getBrowser();

		$this->db('log_action')->create();
		return $this->db('log_action')->save(arrayClean($data));
	}

	/**
	 * 记录用户搜索记录(命中规则)，首页显示最近搜索
	 * @param  string $type    搜索类型(shop|item)
	 * @param  string $sp      商城标识
	 * @param  string $param   商品编号
	 * @param  mix $content  商品详情
	 * @return bool          [description]
	 */
	function search($type, $sp, $param='', $content=''){

		if(!$sp)return;
		if(!$type)return;

		if(is_array($content))$en_content = json_encode($content);

		//做商城合法性判断
		if(!D('shop')->detail($sp))return;
		$data = array();
		$data['type'] = $type;
		$data['content'] = $en_content;
		if(is_array($content) && isset($content['p_seller']))
		$data['seller'] = $content['p_seller'];
		$data['area'] = getAreaByIp();
		$data['client'] = getBrowser();
		$data['user_id'] = D('myuser')->getId();
		$data['sp'] = $sp;
		$data['param'] = $param;
		$data['ip'] = getIp();

		if(!D('myuser')->isLogined()){
			//用户没有登陆，记录到session，登陆时读取记录，插到日志表
			$sess_data = $this->sess('log_search');
			if(!$sess_data){
				$sess_data = array();
			}
			$sess_data[] = $data;
			$this->sess('log_search', $sess_data);
		}

		$this->db('log_search')->create();
		return $this->db('log_search')->save(arrayClean($data));
	}

	/**
	 * 记录用户点击记录
	 * @param  string  $tag  点击标记
	 * @return bool          [description]
	 */
	function click($tag){

		if(!$tag || !D('myuser')->isLogined())return;
		return $this->db('log_click')->save(array('tag'=>$tag, 'user_id'=>D('myuser')->getId()));
	}

	//将用户没有登陆时，记录到session的搜索日志，插到日志表
	function searchSave(){

		if(!D('myuser')->islogined())return false;
		$sess_data = $this->sess('log_search');

		if($sess_data){
			foreach($sess_data as $data){
				$data['user_id'] = D('myuser')->getId();
				$this->db('log_search')->create();
				$this->db('log_search')->save(arrayClean($data));
			}
			$this->sess('log_search', null);
			return true;
		}
		return false;
	}

	//给用户打款后，记录打款日志
	function pay($o_id, $status, $errcode, $alipay='', $cashtype='', $amount='', $api_name='', $api_ret=''){

		$data = array();
		$data['o_id'] = $o_id;
		$data['status'] = $status;
		$data['errcode'] = $errcode;
		$data['alipay'] = $alipay;
		$data['cashtype'] = $cashtype;
		$data['amount'] = $amount;
		$data['api_name'] = $api_name;
		$data['api_ret'] = $api_ret;
		$this->db('log_pay')->create();
		return $this->db('log_pay')->save(arrayClean($data));
	}

	/**
	 * 获取用户最新搜索记录
	 * @param  mix  $type   搜素类型[item|shop]支持数组搜索多类型记录
	 * @param  int  $days   限定记录离当前天数
	 * @param  int  $limit  限定返回结果数量(不超过30个)
	 * @return array        sp,param,item
	 */
	function getRecentSearch($type='item', $days=10, $limit=10){

		$datetime = date('Y-m-d H:i:s', time()-$days*86400);
		$records = $this->db('log_search')->findAll(array('user_id'=>D('myuser')->getId(), 'type'=>$type, 'createtime'=>"> {$datetime}"), '', 'createtime DESC', 30);
		if(!$records)return;
		clearTableName($records);
		$new = array();
		foreach($records as $r){
			if(!isset($new[$r['sp'].'_'.$r['param']]))$new[$r['sp'].'_'.$r['param']] = $r;
		}

		$new = array_slice($new, 0, $limit);

		$fin = array();
		foreach($new as $r){
			$fin[] = array('sp'=>$r['sp'], 'param'=>$r['param'], 'item'=>json_decode($r['content'], true), 'createtime'=>$r['createtime']);
		}

		return $fin;
	}

	/**
	 * 获取用户最新跳转商城记录
	 * @param  int  $days   限定记录离当前天数
	 * @param  int  $limit  限定返回结果数量(不超过30个)
	 * @return array        sp,param,item
	 */
	function getRecentSp($days=10, $limit=10){

		$datetime = date('Y-m-d H:i:s', time()-$days*86400);
		$user_id = D('myuser')->getId();
		$sql = "SELECT sp,max(createtime) createtime FROM log_search WHERE user_id='{$user_id}' AND createtime>'{$datetime}' GROUP BY sp ORDER BY createtime DESC LIMIT {$limit}";
		$records = $this->db('log_search')->query($sql);
		if(!$records)return;
		clearTableName($records);

		return $records;
	}

	/**
	 * 判断用户是否有点击标记
	 * @param  string  $tag  点击标记
	 * @return bool          是否有点击记录
	 */
	function getClick($tag){

		if(!$tag)return;
		return $this->db('log_click')->find(array('user_id'=>D('myuser')->getId(), 'tag'=>$tag));
	}
}
?>