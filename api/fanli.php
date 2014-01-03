<?PHP
//返利网接口访问底层
namespace API;

class Fanli extends _Api {

	/**
	 * 调用返利网passport接口
	 * @param string $api    具体接口路径
	 * @param $array $params 传入接口参数
	 * @param string $secret 签名秘钥
	 */
	function ApiFanliPassport($api, $params, $secret = '9f93eab2452f8dba5c7b9dd49dd85888') {

		$tmp = array();

		$params['t'] = time();
		$params['ip'] = '127.0.0.1';
		ksort($params);

		foreach ($params as $key => $val) {
			$tmp[] = $key . $val;
		}
		$tmp = implode('', $tmp);

		$params['sn'] = md5($tmp . $secret);
		foreach ($params as $key => $value) {
			$p[] = rawurlencode($key) . '=' . rawurlencode($value);
		}
		$p = implode("&", $p);
		return 'http://passport.51fanli.com' . $api . '?' . $p;
	}

}

?>