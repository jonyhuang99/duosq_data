<?php
//DAL:当前用户自身数据访问模块
namespace DAL;

class Myuser extends _Dal {

	/**
	 * 用户支付宝保存
	 * @param  [type] $alipay [description]
	 * @return 用户ID
	 */
	function saveAlipay($alipay, &$err){

		if(!$alipay){
			$err = '支付宝不能为空!';
			return;
		}

		if(!valide($alipay, 'email') && !valide($alipay, 'mobile')){
			$err = '格式错误，请核对账号!';
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
		setcookie('display_name', mask($this->getAlipay()), time() + YEAR, '/');

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

	//获取用户当前等级
	function getLevel(){
		return $this->sess('userinfo.level');
	}

	//获取用户来源风险等级
	function getScRisk(){
		return $this->sess('userinfo.sc_risk');
	}

	//判断用户是否登录
	function isLogined(){
		return $this->sess('userinfo.islogined')?true:false;
	}

	//存取随机计算的新人抽奖集分宝数量
	function newgift($amount=0){

		if($amount){
			$this->sess('newgift', $amount);
		}else{
			$amount = $this->sess('newgift');
			$this->sess('newgift', null);
			return $amount;
		}
	}

	function getCashGift($status=''){

		if(!$this->isLogined())return;
		//1-新人抽奖 2-新人任务 5-新人条件红包
		$sent = D('order')->getSub($this->getId(), 'cashgift', $status);
		$gifttype = array();
		if($sent){
			foreach($sent as $s){
				@$gifttype[$s['gifttype']] += 1;
			}
		}

		return $gifttype;
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
			return $this->sess('userinfo.sp.'.$sp);
		}
	}

	/**
	 * 增加去过的商城次数
	 */
	function addSp($sp){
		if(!$this->isLogined())return;
		$count = intval($this->sess('userinfo.sp.'.$sp));
		$count++;
		$this->sess('userinfo.sp.'.$sp, $count);
		$this->db('user')->save(array('id'=>$this->getId(), 'sp'=>serialize($this->getSp())));
		return $count;
	}
}

?>