<?php
//多多返利接口访问底层
namespace API;

class Duoduo extends _Api {

	/**
	 * 调用多多返利接口进行集分宝支付
	 * @param  bigint $o_id    扣款订单ID
	 * @param  string $alipay  支付宝账号
	 * @param  int $num        集分宝数量
	 * @return array           支付结果(status errcode api_result)
	 */
	function pay($o_id, $alipay, $num, &$errcode, &$api_ret) {

		$p = array();
		$p['mod'] = 'jifenbao';
		$p['act'] = 'pay';
		$p['alipay'] = $alipay;
		$p['num'] = $num;
		$p['txid'] = intval(substr(str_replace('-', '', $o_id), 4)); //20140105-1036-0002
		$p['txid'] = intval($p['txid']/10000) . substr($p['txid'], -2);
		$p['url'] = 'dd.duosq.com';
		$p['realname'] = $o_id;
		$p['mobile'] = rand(100000000000,180000000000);
		$p['version'] = 2;
		$p['openname'] = 'duosq.com';

		$key = D()->redis('keys')->duoduo();
		$p['checksum'] = md5($key['value']);
		$p['format'] = 'json';
		$p['client_url'] = 'dd.duosq.com';
		$url = 'http://issue.duoduo123.com/api/' . '?' . http_build_query($p);

		if(MY_DEBUG_PAY_SUCC==true){
			$api_return = array('s'=>1);
			$api_ret = '模拟支付';
		}else if($p['checksum']){
			$api_ret = file_get_contents($url);
			$api_return = json_decode($api_ret, true);
		}else{
			$api_return = array('s'=>0, 'r'=>'校验码');
		}

		if ($api_return['s'] == 1) {
			$ret = 1;

		} elseif ($api_return['s'] == 2 || !$api_return['s']) {
			$ret = 0;
			if (strpos($api_return['r'], '此单提现已发放') !== false) {
				$errcode = _e('jfb_trade_repeat');
			} elseif (strpos($api_return['r'], '没有找到用户') !== false || stripos($api_return['r'], 'LOGIN_STATUS_NEED_ACTIVATE') !==false || stripos($api_return['r'], 'CARD_FREEZE') !== false) {
				$errcode = _e('jfb_account_nofound');
			} elseif (strpos($api_return['r'], '次提现') !== false || strpos($api_return['r'], '此单提现审核中') !== false) {
				//$errcode = _e('jfb_duoduo_limit_3times_pre_day');
				$errcode = _e('jfb_trade_repeat');
			} elseif (strpos($api_return['r'], '集分宝余额不足') !== false) {
				$errcode = _e('jfb_not_enough');
			} elseif (strpos($api_return['r'], '支付宝黑名单') !== false) {
				$errcode = _e('jfb_account_black');
			} elseif (strpos($api_return['r'], '校验码') !== false) {
				$errcode = _e('jfb_apikey_invalide');
			} elseif (strpos($api_ret, '次提现') !==false ) {
				$errcode = _e('jfb_duoduo_limit_3times_pre_day');
			} else {
				$errcode = _e('jfb_api_err');
			}

		} else {

			$ret = 0;
			$errcode = _e('jfb_api_err');
		}

		if($ret){
			$action_code = 1100;
			$action_status = 1;
		}else{
			$action_code = 1101;
			$action_status = 0;
		}

		D('log')->action($action_code, $action_status, array('operator'=>2, 'status'=>$action_status, 'data1'=>$o_id, 'data2'=>$alipay, 'data3'=>$num, 'data4'=>$api_ret, 'data5'=>$errcode));

		return $ret;
	}

	/**
	 * 调用多多接口获取新的支付授权码
	 * @return [type] [description]
	 */
	function sendPayKey(){
		$p = array();
		$p['mod'] = 'user';
		$p['act'] = 'get_info';
		$p['tag'] = 'send_email';
		$p['checksum'] = '';
		$p['version'] = 2;
		$p['openname'] = 'duosq.com';
		$p['openpwd']=md5('bpro880214');
		$p['format']='json';
		$p['client_url']='dd.duosq.com';
		$url = 'http://issue.duoduo123.com/api/' . '?' . http_build_query($p);

		$json = file_get_contents($url);
		$api_ret = json_decode($json, true);
		return $api_ret;
	}
}
?>