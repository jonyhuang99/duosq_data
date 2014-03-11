<?php
//DAL:当前用户自身数据访问模块
namespace DAL;

class Myuser extends _Dal {

	/**
	 * 用户支付宝保存
	 * @param  [type] $alipay 支付宝账号
	 * @return array          用户ID，账号已存在状态
	 */
	function saveAlipay($alipay, &$err){

		if(!$alipay){
			$err = '支付宝账号不能为空!';
			return;
		}

		if(!valid($alipay, 'email') && !valid($alipay, 'mobile')){
			$err = '支付宝错误，是手机号或邮箱才对哟!';
			return;
		}

		$ret = array();
		if($user_id = $this->db('user')->getIdByAlipay($alipay)){
			$ret['user_id'] = $user_id;
			$ret['exist'] = true;
		}else{
			$this->db()->begin();

			if($user_id = $this->db('user')->add($alipay, D('mark')->getId(), D('mark')->getScRisk())){
				$ret['user_id'] = $user_id;
				$ret['exist'] = false;

				//保存邀请好友
				$parent_id = getCookieParentId();

				if($parent_id){
					D('friend')->addInvite($user_id, $parent_id);
					//互为朋友圈
					if($_GET['allow']=='not'){
						D('friend')->addQuan($parent_id, $user_id, 0);
					}else{
						D('friend')->addQuan($parent_id, $user_id);
					}

				}
				$this->db()->commit();
			}else{
				$this->db()->rollback();
			}
		}

		if(!$ret){
			$err = '系统登陆错误，请稍后尝试，或联系客服！';
		}
		return $ret;
	}

	/**
	 * 用户更改支付宝，仅当新支付宝没人使用
	 * @param  [type] $alipay 支付宝账号
	 * @return bigint         用户ID
	 */
	function changeAlipay($alipay, &$err){

		if(!$alipay){
			$err = '支付宝账号不能为空!';
			return;
		}

		if(!valid($alipay, 'email') && !valid($alipay, 'mobile')){
			$err = '支付宝错误，是手机号或邮箱才对哟!';
			return;
		}

		if(!$this->isLogined()){
			$err = '操作非法，请刷新页面！';
			return;
		}

		$ret = array();
		if($user_id = $this->db('user')->getIdByAlipay($alipay)){
			$err = '您新支付宝已存在，请换一个支付宝!';
			return;
		}else{
			$this->db()->begin();

			if($ret = $this->db('user')->update($this->getId(), array('alipay'=>$alipay))){
				$this->db()->commit();
				$this->relogin();
			}else{
				$this->db()->rollback();
			}
		}

		if(!$ret){
			$err = '系统发生错误，请刷新页面后重试!';
		}
		return $ret;
	}

	/**
	 * 用户登录，设置session
	 * @return [type] [description]
	 */
	function login($userid){

		if(!$userid)return;
		$user = D('user')->detail($userid);
		if(!$user)return;
		//加载个人信息到session
		$user['islogined'] = true;
		if($user['sp'])$user['sp'] = unserialize($user['sp']);


		$this->sess('userinfo', $user);
		//如果用户在未登录前有过搜索日志，此处加入到log_search
		D('log')->searchSave();

		//加载到cookie方便静态js直接调用
		setcookie('display_name', $this->getNickname(), time() + YEAR, '/');

		return true;
	}

	//更新用户信息后，重新刷新用户session数据
	function relogin(){
		return $this->login($this->getId());
	}

	//获取用户ID
	function getId(){
		return $this->sess('userinfo.id');
	}

	//获取用户支付宝
	function getAlipay(){
		return $this->sess('userinfo.alipay');
	}

	//获取用户支付宝验证信息
	function getAlipayValid(){
		return D('user')->detail($this->getId(), 'alipay_valid');
	}

	//获取用户当前等级
	function getLevel(){
		return $this->sess('userinfo.level');
	}

	//获取用户来源
	function getReferer(){
		return $this->sess('userinfo.referer');
	}

	//获取用户来源风险等级
	function getScRisk(){
		return $this->sess('userinfo.sc_risk');
	}

	//获取用户来源标识ID
	function getMarkId(){
		return $this->sess('userinfo.mark_id');
	}

	//获取用户昵称
	function getNickname(){

		if($this->sess('userinfo.nickname')){
			return $this->sess('userinfo.nickname');
		}else{
			return mask($this->getAlipay());
		}
	}

	//判断用户是否登录
	function isLogined(){
		return $this->sess('userinfo.islogined')?true:false;
	}

	//判断用户是否抢过官方红包
	function hasRobtime(){
		return D('user')->detail($this->getId(), 'has_robtime');
	}

	//判断用户是否允许赠送新人红包
	function canGetCashgift(){
		return D('user')->detail($this->getId(), 'can_get_cashgift');
	}

	//判断用户是否黑名单
	function isBlack(){
		return D('user')->isBlack($this->getId());
	}

	//存取随机计算的新人抽奖集分宝数量
	function newgift($amount=0, $get=false){

		if(!$get){
			if($amount){
				$this->sess('newgift', $amount);
			}else{
				$amount = $this->sess('newgift');
				$this->sess('newgift', null);
				return $amount;
			}
		}else{
			$amount = $this->sess('newgift');
			return $amount;
		}
	}

	/**
	 * 获取去过的商城/判断是否去过某商城($sp赋值)
	 * 用途：跳转页面出现首次提醒
	 * @param  [string] $sp 商城标识，如果不为空则提取具体商城跳转次数
	 */
	function getSp($sp=''){
		if(!$sp){
			return $this->sess('userinfo.sp');
		}else{
			return intval($this->sess('userinfo.sp.'.$sp));
		}
	}

	//增加当前用户去过的商城次数
	function addSp($sp){

		if(!$this->isLogined())return;
		$count = intval($this->sess('userinfo.sp.'.$sp));
		$count++;
		$this->sess('userinfo.sp.'.$sp, $count);
		$this->db('user')->update($this->getId(), array('sp'=>serialize($this->getSp())));
		return $count;
	}

	//更新自己的昵称
	function updateNickname($nickname){

		if(!$this->isLogined())return;
		if(strlen($nickname)>20)return false;
		if(!$nickname){
			$this->db('user')->update($this->getId(), array('nickname'=>$nickname));
			$this->sess('userinfo.nickname', $nickname);
			setcookie('display_name', $this->getNickname(), time() + YEAR, '/');
			return true;
		}
	}

	/**
	 * 获取当前用户的红包信息
	 * @return [type] [description]
	 */
	function getCashGift($status=''){

		if(!$this->isLogined())return;
		return D('cashgift')->getSummary($this->getId(), $status);
	}

	//标记打开小金库的时间
	function markOpenCenter($type='set', $time=''){

		if($type == 'set'){
			if(!$time){
				$time = time();
			}
			//已有时间是超前的，则不用更新
			if($this->sess('open_center') && $this->sess('open_center')>$time)return;
			$this->sess('open_center', $time);
		}else{
			return $this->sess('open_center');
		}
	}

	//更新最后登录时间
	function updateLasttime(){

		if(!$this->isLogined())return;
		$this->db('user')->update($this->getId(), array('lasttime'=>date('Y-m-d H:i:s')));
	}
}

?>