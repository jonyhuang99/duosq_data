<?php
//DAL:朋友关系管理模块
namespace DAL;

class Friend extends _Dal {

	/**
	 * 增加好友邀请关系
	 * @param  bigint $user_id   被邀请用户ID
	 * @param  bigint $parent_id 上游用户ID
	 * @return [type]            [description]
	 */
	function addInvite($user_id, $parent_id){

		if(!$user_id || !$parent_id)return;
		if($user_id == $parent_id)return;
		$this->db('friend_invite')->create();
		return $this->db('friend_invite')->save(array('user_id'=>$user_id, 'parent_id'=>$parent_id));
	}

	/**
	 * 获取上游用户邀请好友情况
	 * @param  bigint $parent_id 上游用户ID
	 * @param  integer $in_days   [description]
	 * @return [type]             [description]
	 */
	function getInvitedUsers($parent_id, $in_days = 0){

		if(!$parent_id)return;
		if($in_days){
			$after = date('Y-m-d', time() - $in_days * DAY);
			$users = $this->db('friend_invite')->findAll(array('parent_id'=>$parent_id, 'createtime'=>"> {$after}"));
		}else{
			$users = $this->db('friend_invite')->findAll(array('parent_id'=>$parent_id));
		}

		return clearTableName($users);
	}

	/**
	 * 获取上游用户邀请好友数量
	 * @param  bigint $parent_id 上游用户ID
	 * @param  integer $in_days   [description]
	 * @return [type]             [description]
	 */
	function getInviteUserNum($parent_id, $in_days = 0){

		if(!$parent_id)return;
		if($in_days){
			$after = date('Y-m-d', time() - $in_days * DAY);
			$num = $this->db('friend_invite')->findCount(array('parent_id'=>$parent_id, 'createtime'=>"> {$after}"));
		}else{
			$num = $this->db('friend_invite')->findCount(array('parent_id'=>$parent_id));
		}

		return $num;
	}

	/**
	 * 判断是否已经发过好友请求
	 * @param bigint  $sender   主动添加人
	 * @param bigint  $recevier 被邀请人
	 */
	function friendAsked($sender, $recevier){

		if(!$sender || !$recevier)return;
		return $this->db('friend_quan')->find(array('sender'=>$sender, 'recevier'=>$recevier));
	}

	//待处理的好友请求
	function friendAskedList($recevier){

		if(!$recevier)return;
		$ret = $this->db('friend_quan')->findAll(array('recevier'=>$recevier, 'agree'=>0));
		clearTableName($ret);
		return $ret;
	}

	//处理好友的请求
	function friendAgree($req_id, $recevier, $agree = 1){

		$detail = $this->db('friend_quan')->find(array('id'=>$req_id));
		if(!$detail)return;

		clearTableName($detail);
		if($detail['recevier'] != $recevier){
			return;
		}

		return $this->db('friend_quan')->save(array('id'=>$req_id, 'agree'=>$agree));
	}

	/**
	 * 判断是否已是好友关系
	 * @param bigint  $sender   主动添加人
	 * @param bigint  $recevier 被邀请人
	 */
	function isFriend($sender, $recevier){

		$ret = $this->db('friend_quan')->find(array('sender'=>$sender, 'recevier'=>$recevier, 'agree'=>1));
		if($ret)return true;
		$ret = $this->db('friend_quan')->find(array('sender'=>$recevier, 'recevier'=>$sender, 'agree'=>1));
		if($ret)return true;
	}

	/**
	 * 增加朋友圈关系
	 * @param bigint  $sender   主动添加人
	 * @param bigint  $recevier 被邀请人
	 * @param integer $agree    [description]
	 */
	function addQuan($sender, $recevier, $agree=1){

		if(!$sender || !$recevier)return;
		if($sender == $recevier)return;

		if($this->db('friend_quan')->find(array('sender'=>$sender, 'recevier'=>$recevier))){
			return false;//不能重复发起申请
		}

		if($agree){
			if($this->db('friend_quan')->find(array('sender'=>$recevier, 'recevier'=>$sender, 'agree'=>1))){
				return false;
			}
		}

		if($agree){
			$ret = $this->db('friend_quan')->save(array('sender'=>$sender, 'recevier'=>$recevier, 'agree'=>$agree, 'agreetime'=>date('Y-m-d H:i:s')));
		}else{
			$ret = $this->db('friend_quan')->save(array('sender'=>$sender, 'recevier'=>$recevier, 'agree'=>$agree));
		}

		return $ret;
	}

	/**
	 * 获得用户的所有好友
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	function getQuanFriends($user_id, $inc_sys=true){

		if(!$user_id)return false;
		$all = $this->db('friend_quan')->findAll("(sender='{$user_id}' OR recevier='{$user_id}') AND agree=1", '', 'id DESC');
		clearTableName($all);
		$friends = array();
		if($all){
			foreach($all as $friend){
				$friends[$friend['recevier']] = 1;
				$friends[$friend['sender']] = 1;
			}
		}

		if($inc_sys){
			$friends[C('comm', 'sysuser_friend')] = 1;
		}

		unset($friends[$user_id]);

		return array_keys($friends);
	}

	/**
	 * 好友获得购物省钱后，生成省钱圈内红包
	 * @param char $o_id 订单编号
	 */
	function addQuanReward($o_id){

		if(!$o_id)return;
		$detail = D('order')->detail($o_id);
		if($detail['status'] == \DAL\Order::STATUS_PASS){

			//仅当好友关系超过N人时有效
			$friends = $this->getQuanFriends($detail['user_id']);
			if(count($friends) < 2)return false;

			$amount = ceil($detail['amount'] * C('comm', 'friend_quan_reward_rate')/100);
			if($amount){
				$this->db('friend_quan_reward')->create();
				$ret = $this->db('friend_quan_reward')->save(array('user_id'=>$detail['user_id'], 'o_id'=>$o_id, 'amount'=>$amount));
				if($ret){
					//发送红包产生知会消息
					D('notify')->addQuanRewardCreatedJob($o_id);

					return array('user_id'=>$detail['user_id'], 'amount'=>$amount);
				}else{
					return false;
				}
			}
		}
	}

	/**
	 * 获取省钱圈红包列表
	 * @param  bigint $user_id 用户ID
	 * @return array           红包列表
	 */
	function getQuanRewardList($user_id){

		$friends = $this->getQuanFriends($user_id);
		$list = $this->db('friend_quan_reward')->findAll(array('user_id'=>$friends), '', 'createtime DESC', 20);
		clearTableName($list);

		//渲染官方红包记录
		$has_robtime = D('myuser')->hasRobtime();
		foreach($list as &$reward){
			if($reward['id'] == 1){

				$reward['user_nickname'] = '多省钱官方赠送';
				$reward['recevietime'] = $has_robtime;
				if($has_robtime != '0000-00-00 00:00:00'){
					$reward['recevier_nickname'] = '您已领取';
				}
			}else{

				$reward['user_nickname'] = D('user')->getNickname($reward['user_id']);
				if($reward['recevier']){
					$reward['recevier_nickname'] =  D('user')->getNickname($reward['recevier']);
				}
			}
		}

		return $list;
	}

	/**
	 * 获取省钱圈红包详情
	 * @param  [type] $quan_reward_id [description]
	 * @return [type]                 [description]
	 */
	function getQuanRewardDetail($id, $is_o_id=false){

		if($is_o_id){
			$detail = $this->db('friend_quan_reward')->find(array('o_id'=>$id));
		}else{
			$detail = $this->db('friend_quan_reward')->find(array('id'=>$id));
		}

		return clearTableName($detail);
	}

	/**
	 * 保存省钱圈红包祝福语
	 * @param  [type] $quan_reward_id [description]
	 * @param  [type] $bless          [description]
	 * @return [type]                 [description]
	 */
	function saveQuanRewardBless($quan_reward_id, $bless){

		$detail = $this->getQuanRewardDetail($quan_reward_id);
		if(!$detail['bless'] && $detail['recevier'] == D('myuser')->getId()){
			return $this->db('friend_quan_reward')->save(array('id'=>$quan_reward_id, 'bless'=>$bless));
		}
	}

	/**
	 * 争抢圈内红包
	 * @param  int $quan_reward_id 红包赠送ID
	 * @return int                 0:红包被抢 1:抢夺成功 2:有朋友正在抢夺，请重试
	 */
	function robQuanReward($quan_reward_id){

		if(!$quan_reward_id)return 0;

		$ret = $this->redis('lock')->getlock(\Redis\Lock::LOCK_QUAN_REWARD, $quan_reward_id);
		if(!$ret)return 2;

		$luck = $this->db('friend_quan_reward')->find(array('id'=>$quan_reward_id, 'recevier'=>0));

		if($luck){
			clearTableName($luck);

			//不准抢其他省钱圈的红包，官方红包除外
			if($quan_reward_id!=1){
				$friends = $this->getQuanFriends(D('myuser')->getId());
				if(!in_array($luck['user_id'], $friends)){
					$ret = $this->redis('lock')->unlock(\Redis\Lock::LOCK_QUAN_REWARD, $quan_reward_id);
					return 0;
				}
			}else{
				//不准重复抢官方红包
				if(D('myuser')->hasRobtime()!='0000-00-00 00:00:00')return 0;
			}

			//不允许抢自己的红包
			if($luck['user_id'] == D('myuser')->getId()){
				$ret = $this->redis('lock')->unlock(\Redis\Lock::LOCK_QUAN_REWARD, $quan_reward_id);
				return 0;
			}

			$this->db()->begin();

			//官方红包不需更新
			if($quan_reward_id!=1){
				$ret1 = $this->db('friend_quan_reward')->save(array('id'=>$quan_reward_id, 'recevier'=>D('myuser')->getId(), 'recevietime'=>date('Y-m-d H:i:s')));
			}else{
				$ret1 = $this->db('user')->update(D('myuser')->getId(), array('has_robtime'=>date('Y-m-d H:i:s')));
			}

			if($ret1){
				D('order')->db('order_cashgift');
				$ret2 = D('order')->addCashgift(D('myuser')->getId(), \DB\OrderCashgift::GIFTTYPE_QUAN, $luck['amount'], $luck['o_id']);
			}

			if($ret1 && $ret2){
				$this->db()->commit();
				$ret = $this->redis('lock')->unlock(\Redis\Lock::LOCK_QUAN_REWARD, $quan_reward_id);
				return 1;
			}else{
				$this->db()->rollback();
				$ret = $this->redis('lock')->unlock(\Redis\Lock::LOCK_QUAN_REWARD, $quan_reward_id);
				return 0;
			}

		}else{
			$ret = $this->redis('lock')->unlock(\Redis\Lock::LOCK_QUAN_REWARD, $quan_reward_id);
			return 0;
		}
	}

	/**
	 * 获取上游用户ID(推荐人ID)
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	function getParentId($user_id){

		$ret = $this->db('friend_invite')->field('parent_id', array('user_id'=>$user_id));
		return $ret;
	}
}
?>