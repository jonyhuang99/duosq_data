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

		$order['user_id'] = C('comm', 'sysuser_promo');
		return $order;

		if($order['buydatetime'] == '0000-00-00 00:00:00' || date('H:i:s', strtotime($order['buydatetime'])) == '00:00:00'){
			$buydatetime = date('Y-m-d H:i:s', strtotime($order['buydate'])+DAY);
		}else{
			$buydatetime = date('Y-m-d H:i:s', strtotime($order['buydatetime'])+MINUTE*5);
		}

		$before = strtotime($buydatetime);
		$before = date('Y-m-d', $before - DAY*15);

		$hit_outcode = D()->db('outcode')->query("SELECT * FROM outcode WHERE createtime <= '{$buydatetime}' AND createtime >= '{$before}' AND param = '{$order['r_id']}' GROUP BY user_id");
		clearTableName($hit_outcode);

		//校验该店铺是否被其他人访问过，以免错单
		$bak_user_ids = array();
		if($order['r_wangwang']){

			$ret = D('log')->db('log_search')->query("SELECT * FROM log_search WHERE createtime <= '{$buydatetime}' AND createtime >= '{$before}' AND seller = '{$order['r_wangwang']}' AND user_id <> 0 GROUP BY user_id");
			clearTableName($ret);

			if($ret){
				foreach($ret as $log){
					$bak_user_ids[] = $log['user_id'];
				}

				$debug['shop_user_ids'] = $bak_user_ids;
				//TODO 如果超过1个人访问过该店铺，且其他人存在无outcode或outcode match该订单，无效
			}else{
				$hit_outcode = false;
			}
		}

		$debug['hit_outcode'] = $hit_outcode;

		//遇到重复，则报错
		if($hit_outcode){

			if(count($hit_outcode)>1){
				$hit_outcode = false;
			}else{
				$hit_outcode = $hit_outcode[0];
				//必须是半小时内浏览的用户，且14天内无其他用户浏览，才认为这个人是单的主人，防止1小时前其他用户访问过，但没下单，真实用户仅访问了店铺(但没访问过该商品)，下单了
				if(strtotime($buydatetime) - strtotime($hit_outcode['createtime']) > 3600){
					$hit_outcode = false;
				}

				//10分钟内，有其他用户浏览过店铺(虽然没有直接访问过该商品)，有可能误判
				$before = strtotime($buydatetime);
				$before = date('Y-m-d', $before - MINUTE * 10);
				$recent_shop_ret = D('log')->db('log_search')->query("SELECT * FROM log_search WHERE createtime <= '{$buydatetime}' AND createtime >= '{$before}' AND seller = '{$order['r_wangwang']}' AND user_id <> 0 GROUP BY user_id");
				clearTableName($hit_outcode);
				if(count($recent_shop_ret)>1){
					$hit_outcode = false;
				}
			}
		}

		$taobao_no = getTaobaoNo($order['r_orderid']);
		$order['r_taobao_no'] = $taobao_no;

		//初始化默认跟单用户ID，比例
		$user_id = C('comm', 'sysuser_order_taobao_trace_error');
		$fanli_rate = C('comm', 'fanli_taobao_rate');

		if(!$hit_outcode){

			//用taobao_no匹配用户
			$user_ids = D('user')->getUserByTaobaoNo($taobao_no);

			$debug['taobao_no_to_user_ids'] = $user_ids;

			//该用户当天必须唯一的访问过此店
			if($user_ids){
				if($bak_user_ids){
					$user_ids = array_intersect($user_ids, $bak_user_ids);
					if(count($user_ids) == 1){
						$user_id = array_pop($user_ids);
					}
				}
			}

			if($user_id < 100 && $bak_user_ids && count($bak_user_ids) == 1){
				//该用户访问的是店铺
				$user_id = $bak_user_ids[0];
			}

		}else{

			$user_id = $hit_outcode['user_id'];
			$fanli_rate = $hit_outcode['fanli_rate'];
			if(!$hit_outcode['fanli_rate']) $fanli_rate = C('comm', 'fanli_taobao_rate');
		}

		//修正用户购买同一个商品，不同r_taobao_no，进入审核，防止大量误assign
		if(!D('user')->sys($user_id)){

			//该订单必须是新增的，此处修正查找同订单，不同商品比例不同导致旧佣金比例错乱
			$order_existed = D('order')->getSubList('taobao', array('r_orderid'=>$order['r_orderid'], 'r_id'=>$order['r_id']));

			$hit = D('order')->getSubList('taobao', array('user_id'=>$user_id,'r_taobao_no'=>"<> {$taobao_no}", 'r_id'=>$order['r_id']));
			if($hit){

				if(!$order_existed){
					$user_id = C('comm', 'sysuser_order_taobao_trace_error');
				}
			}
		}

		$debug['user_id'] = $user_id;

		//更新用户标识符
		if(!D('user')->sys($user_id) && $update_taobao_no){//跳过系统账号

			//仅新增时做mapping，修正用户userid变化，导致旧的taobao_no关系仍然继续命中，导致继续产生无效taobao_no
			if(!$order_existed)
				D('user')->addTaobaoNo($user_id, $taobao_no);

			//标识大于4人，加入黑名单需提供证据解锁
			$user_taobao_no = D('user')->getTaobaoNo($user_id, true);
			if(count($user_taobao_no) > 4){
				D('user')->markBlack($user_id, \DAL\User::STATUS_BLACK_2, 'taobao_no');
			}
		}

		$order['user_id'] = $user_id;
		//计算给用户的返利
		$order['fanli_rate'] = $fanli_rate;

		//加上用户等级修正
		$order['fanli_lv_rate'] = D('user')->lvRate($user_id);

		//订单如果已存在，fanli_rate、fanli_lv_rate、r_yongjin_rate、r_yongjin使用历史快照
		if($order_existed){
			$existed = array_shift($order_existed);
			if($existed['fanli_rate']){
				$order['fanli_rate'] = $existed['fanli_rate'];
				$order['fanli_lv_rate'] = $existed['fanli_lv_rate'];
				$order['r_yongjin_rate'] = $existed['r_yongjin_rate']; //此处信任taobao佣金比例不会改变
				//$order['r_yongjin'] = $existed['r_yongjin'];
			}
		}

		$debug['fanli_rate'] = $order['fanli_rate'];
		$debug['fanli_lv_rate'] = $order['fanli_lv_rate'];

		//黑名单用户修改相应返利比例
		$user_status = D('user')->getStatus($user_id);

		if($user_status == \DAL\User::STATUS_BLACK_1 || $user_status == \DAL\User::STATUS_BLACK_2){
			$reason = D('user')->detail($user_id, 'reason');
			//如果是恶意注册，一旦有订单，可以解封
			if($reason == 'reg_acttack'){
				D('user')->unMarkBlack($user_id);//用户一旦有订单后就解锁
			}
		}

		$order['fanli'] = ceil($order['r_yongjin'] * ($order['fanli_rate']+$order['fanli_rate']*$order['fanli_lv_rate']/100) /100);

		return $order;
	}
}
?>