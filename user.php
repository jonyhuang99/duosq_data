<?php
namespace DAL;
/**
 * DAL:用户数据访问层
 */
class User extends _Dal {

	function getUser($userid, $field){

		if(!$userid || !$field || $field == '*')return;

		return clearTableName($this->db('user')->find(array('userid'=>$userid), $field));
	}

	/**
	 * 用户支付宝保存
	 * @param  [type] $alipay [description]
	 * @return 用户ID
	 */
	function saveAlipay($alipay){

		if(!$alipay)return;

		$ret = array();
		if($user_id = $this->db('user')->getIdByAlipay($alipay)){
			$ret['user_id'] = $user_id;
			$ret['exist'] = true;
		}else{
			if($user_id = $this->db('user')->add($alipay)){
				$ret['user_id'] = $user_id;
				$ret['exist'] = false;
			}
		}
		return $ret;
	}

	/**
	 * 用户登录，设置session
	 * @return [type] [description]
	 */
	function login($userid){

		if(!$userid)return;

		$user = $this->db('user')->getInfo($userid);
		if(!$user)return;
		//加载到session
		$user['islogined'] = true;
		$this->sess('userinfo', $user);
		//加载到cookie方便静态js直接调用
		setcookie('display_name', mask($this->getAlipay()), time() + YEAR, '/');

		return true;
	}

	function getId(){
		return $this->sess('userinfo.id');
	}

	function getAlipay(){
		return $this->sess('userinfo.alipay');
	}

	function isLogined(){

		return $this->sess('userinfo.islogined')?true:false;
	}
}

?>