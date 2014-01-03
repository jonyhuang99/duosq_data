<?php
//DAL:用户标识信息访问模块
namespace DAL;

class Mark extends _Dal {

	/**
	 * 返回来源渠道信息，但有可能不存在
	 * @return [type] [description]
	 */
	function detail($mark_id=0) {

		if(!$mark_id){
			$mark_id = $this->getId();
			if(!$mark_id)return false;
		}
		$ret = $this->db('mark')->find(array('id'=>$mark_id));
		return clearTableName($ret);
	}

	/**
	 * 返回来源渠道名称，但有可能不存在
	 * @return [type] [description]
	 */
	function getSc(){

		$info = $this->detail();
		return myIsset($info['sc']);
	}

	/**
	 * 获取渠道风险等级
	 * @return [type] [description]
	 */
	function getScRisk(){

		$info = $this->detail();
		$sc_risk = 0;
		if($info){
			$source_conf = C('comm', 'source');
			$sc_risk = myIsset($source_conf[$info['sc']]['risk'], 0);
		}

		return $sc_risk;
	}

	/**
	 * 返回渠道编号
	 * @return [type] [description]
	 */
	function getId(){
		return intval(@$_COOKIE['mark']);
	}

	/**
	 * 添加用户流量信息
	 */
	function add($sc){

		if($this->getId())return;
		$data = array();
		$data['sc'] = $sc;
		$data['referer'] = env('HTTP_REFERER');
		$data['ip'] = getIp();

		I('ip2location');
		$ip = new \ip2location();
		$data['area'] = $ip->province($data['ip']);
		$data['area_detail'] = $ip->location($data['ip']);

		$data['client'] = getBrowser();
		$mark_id = $this->db('mark')->save(arrayClean($data));
		if($mark_id){
			setcookie('mark', $mark_id, time() + YEAR, '/');
		}
	}
}
?>