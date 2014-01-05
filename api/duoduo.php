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
	function pay($o_id, $alipay, $num, &$errcode) {

		$p = array();
		$p['mod'] = 'jifenbao';
		$p['act'] = 'pay';
		$p['alipay'] = $alipay;
		$p['num'] = $num;
		$p['txid'] = intval(str_replace('-', '', $o_id));
		$p['url'] = 'dd.duosq.com';
		$p['realname'] = $o_id;
		$p['mobile'] = $o_id;
		$p['version'] = 2;
		$p['openname'] = 'duosq.com';
		$p['checksum'] = md5(C('keys', 'duoduo_pay')).'xxx';
		$p['format'] = 'json';
		$p['client_url'] = 'dd.duosq.com';
		$url = 'http://issue.duoduo123.com/api/' . '?' . http_build_query($p);

		if(MY_DEBUG_PAY_SUCC==true){
			$api_ret = array('s'=>1);
		}else{
			$json = file_get_contents($url);
			$api_ret = json_decode($json, true);
		}

		if ($api_ret['s'] == 1) {
			$ret = 1;

		} elseif ($api_ret['s'] == 2) {
			$ret = 0;

			if (strpos($api_ret['r'], '此单提现已发放') !== false) {
				$errcode = E('jfb_trade_repeat');
			} elseif (strpos($api_ret['r'], '没有找到用户') !== false) {
				$errcode = E('jfb_account_nofound');
			} elseif (strpos($api_ret['r'], '支付宝一日内第3次提现') !== false) {
				$errcode = E('jfb_duoduo_limit_3times_pre_day');
			} else {
				$errcode = E('jfb_unknow');
			}

		} elseif ($api_ret['s'] == 0 && strpos($api_ret['r'], '校验码') !== false){

			$ret = 0;
			$errcode = E('jfb_apikey_invalide');

		} else {
			$ret = 0;
			$errcode = E('jfb_unknow');
		}

		if($ret){
			$action_code = 1100;
			$action_status = 1;
		}else{
			$action_code = 1101;
			$action_status = 0;
		}

		D('log')->action($action_code, 1, array('operator'=>2, 'status'=>$action_status, 'data1'=>$o_id, 'data2'=>$alipay, 'data3'=>$num, 'data4'=>serialize($api_ret)));

		return $ret;
	}
}
?>