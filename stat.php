<?php
//DAL:统计模块
namespace DAL;

class Stat extends _Dal {

	//流量交换，按IP记录数量
	function friendIpAdd($tag){

		$this->redis('counter')->ipadd($tag, MONTH, date('Ymd'));
	}

	//流量交换，进行扣除
	function friendIpDel($tag){

		return $this->redis('counter')->ipdel($tag, date('Ymd'));
	}

	//流量交换，进行扣除
	function friendIpCount($tag){

		return $this->redis('counter')->ipcount($tag, date('Ymd'));
	}
}
?>