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
		$this->db('friend_invite')->create();
		return $this->db('friend_invite')->save(array('user_id'=>$user_id, 'parent_id'=>$parent_id));
	}

	/**
	 * 增加朋友圈关系
	 * @param bigint  $sender   主动添加人
	 * @param bigint  $recevier 被邀请人
	 * @param integer $agree    [description]
	 */
	function addQuan($sender, $recevier, $agree=1){

		if($this->db('friend_quan')->find(array('sender'=>$sender, 'recevier'=>$recevier))){
			return false;//不能重复发起申请
		}

		if($agree){
			if($this->db('friend_quan')->find(array('sender'=>$recevier, 'recevier'=>$sender, 'agree'=>1))){
				return false;
			}
		}

		if($agree){
			$ret = $this->db('friend_quan')->find(array('sender'=>$sender, 'recevier'=>$recevier, 'agree'=>$agree, 'agreetime'=>date('Y-m-d H:i:s')));
		}else{
			$ret = $this->db('friend_quan')->find(array('sender'=>$sender, 'recevier'=>$recevier, 'agree'=>$agree));
		}

		return $ret;
	}

	function quanFriends($user_id){

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