<?php
//DAL:淘宝订单相关操作封装
namespace DAL;

class OrderTaobao extends _Dal {

	/**
	 * 测试订单：603702089630292
	 * 淘宝订单匹配用户，如果匹配不上用户，全部跟单到系统跟单账号
	 * @param  [type] $order [description]
	 * @return [type]        [description]
	 */
	//TODO 改匹配算法正式拿到接口后，迁移成go模块进行outcode匹配
	function matchOutcode($order, $update_taobao_no=true, &$debug=''){

		$taobao_no = getTaobaoNo($order['r_orderid']);
		$order['r_taobao_no'] = $taobao_no;
		$order['user_id'] = C('comm', 'sysuser_order_taobao_trace_error');
		$order['fanli_rate'] = C('comm', 'fanli_taobao_rate');

		//该订单必须是新增的，此处修正查找同订单，不同商品比例不同导致旧佣金比例错乱
		$order_existed = D('order')->getSubList('taobao', array('r_orderid'=>$order['r_orderid'], 'r_id'=>$order['r_id']));
		//订单如果已存在，fanli_rate、fanli_lv_rate、r_yongjin_rate、r_yongjin使用历史快照
		if($order_existed){
			$existed = array_shift($order_existed);
			if($existed['fanli_rate']){
				$order['fanli_rate'] = $existed['fanli_rate'];
				$order['fanli_lv_rate'] = $existed['fanli_lv_rate'];
				$order['r_yongjin_rate'] = $existed['r_yongjin_rate']; //此处信任taobao佣金比例不会改变
				$order['user_id'] = $existed['user_id']; //此处信任taobao佣金比例不会改变
				//$order['r_yongjin'] = $existed['r_yongjin'];
			}
		}else{
			//fanli_rate需要根据佣金额度打折
			if($order['r_yongjin'] >= 500){
				$order['fanli_rate'] = $order['fanli_rate'] * (C('comm', 'fanli_taobao_5_rate') / 100);
			}elseif($order['r_yongjin'] >= 1000){
				$order['fanli_rate'] = $order['fanli_rate'] * (C('comm', 'fanli_taobao_10_rate'));
			}

			$order['fanli'] = ceil($order['r_yongjin'] * ($order['fanli_rate'] /100));
		}

		return $order;
	}
}
?>