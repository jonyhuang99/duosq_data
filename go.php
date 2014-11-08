<?php
//DAL:跳转跟单模块
namespace DAL;

class Go extends _Dal {

	/**
	 * 产生跳转跟单码
	 * @param  string $driver 驱动
	 * @param  array $p       sp[商城标识] tc[渠道码] param[商品编号]
	 * @return string         跟单码
	 */
	function createOutcode($driver, $sp, $param='', $tc='') {

		if(!$driver || !$sp)return;
		$outcode = $this->redis('outcode')->create();
		$fanli = $driver->getCommission($sp);
		$driver_n = low(str_replace('GO_DRIVER\\Driver', '', get_class($driver)));

		if(!D('myuser')->isLogined()){
			$user_id = C('comm', 'sysuser_promo');
		}else{
			$user_id = D('myuser')->getId();
		}

		$data = array('user_id'=>$user_id, 'driver'=>$driver_n, 'sp'=>$sp, 'param'=>$param, 'tc'=>$tc, 'outcode'=>$outcode, 'cashtype'=>$fanli['cashtype'], 'fanli_rate' => $driver->getFanliRate());

		if($this->db('outcode')->add(arrayClean($data))){
			return $outcode;
		}else{
			return false;
		}
	}

	/**
	 * 获取outcode详情
	 * @param  string $outcode 跟单码
	 * @return int          用户ID
	 */
	function decodeOutcode($outcode){

		if(!$outcode)return;
	}

	/**
	 * 获取支持本商家的跳转驱动
	 * @return [type] [description]
	 */
	function getDriver($sp){
		if(!$sp)return;

		if(taobaoSp($sp))$sp = 'taobao';

		static $loaded = array();
		if(isset($loaded[$sp]))return $loaded[$sp];

		//调度跳转驱动，进行跳转
		I('go_driver/driver');
		//当前支持的驱动配置，myconfig/comm.php go_drivers维护
		foreach(C('comm', 'go_drivers') as $driver_name){
			I('go_driver/driver_'.low($driver_name));
			$class_name = "\GO_DRIVER\Driver".ucfirst($driver_name);
			$driver = new $class_name();

			if($driver->supported($sp)){
				$loaded[$sp] = $driver;
				return $loaded[$sp];
			}
		}
	}
}
?>