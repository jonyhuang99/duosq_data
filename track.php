<?php
//DAL:用户跟踪模块
namespace DAL;

class Track extends _Dal {

	//获取用户跟踪码
	function get(){

		if(@$_COOKIE['__utmo']){
			return $_COOKIE['__utmo'];
		}else{
			return '';
		}
	}

	//初始化用户跟踪码
	function init() {

		if (isset($_COOKIE['__utmo'])) {
			$t = explode('.', $_COOKIE['__utmo']);
			if (count($t) == 3) {
				$time = $this->gtcDecode($t[0]);
				$ip = long2ip($this->gtcDecode($t[1]));
				$check = $t[2];
				if ($check != substr(preg_replace('/[^\d]/', '', md5($time . $ip)) . '000', 0, 3)) {
					unset($_COOKIE['__utmo']);
				}
			} else {
				unset($_COOKIE['__utmo']);
			}
		}

		if (!isset($_COOKIE['__utmo'])) {
			$time = time();
			$ip = getIp();
			$check = substr(preg_replace('/[^\d]/', '', md5($time . $ip)) . '000', 0, 3);
			$__utmo = $this->gtcEncode($time) .'.'. $this->gtcEncode(ip2long($ip)) .'.'. $check;
			$t = session_get_cookie_params();
			setcookie('__utmo', $__utmo, $time+63072000, $t['path'], $t['domain']);
		}
	}

	private function gtcEncode($num) {
		return reset(unpack('N', pack('V', $num ^ 168273)));
	}

	private function gtcDecode($num) {
		return reset(unpack('V', pack('N', $num))) ^ 168273;
	}
}
?>