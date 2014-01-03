<?php
//DAL:跳转跟单模块
namespace DAL;

class Go extends _Dal {

	/**
	 * 产生跳转跟单码
	 * @param  string $driver 驱动名称
	 * @param  array $p       sp[商城标识] tc[渠道码] param[商品编号]
	 * @return string         跟单码
	 */
	function createOutcode($driver, $sp, $param='', $tc='') {

		if(!$driver || !$sp)return;
		$outcode = $this->redis('outcode')->create();
		$fanli = $this->getCommission($sp);

		$driver = str_replace('GO_DRIVER\\', '', $driver);
		$user_id = D('myuser')->getId();
		$data = array('user_id'=>$user_id, 'driver'=>$driver, 'sp'=>$sp, 'param'=>$param, 'tc'=>$tc, 'outcode'=>$outcode, 'cashtype'=>$fanli['cashtype'], 'fanli_rate' => C('comm', 'fanli_rate'));

		$this->db('outcode')->create();
		if($this->db('outcode')->save(arrayClean($data))){
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

	/**
	 * 根据商家佣金比例
	 * @param  string $sp 商城标识
	 * @param  bool $fanli 获取给会员的返利信息
	 * @return [array] commision:佣金比例   cashtype:1-集分宝 2-现金
	 */
	function getCommission($sp, $fanli=true){

		$driver = $this->getDriver($sp);
		if(!$driver)return;
		$info = $driver->supported($sp);

		$ret = array('commission'=>$info['commission'], 'cashtype'=>$info['cashtype']);
		if(strpos($ret['commission'], '元')===false){
			$ret['commission'] .= '%';
		}

		if($fanli){
			if(@$info['commission_fanli']){
				$ret['commission'] = $info['commission_fanli'];
			}
		}else{
			if(@$info['commission_union']){
				$ret['commission'] = $info['commission_union'];
			}
		}

		return $ret;
	}
}
?>