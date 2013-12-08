<?php
namespace DAL;
/**
 * DAL:用户数据访问
 */
class User extends _Dal {

	function getUser($userid, $field){

		if(!$userid || !$field || $field == '*')return;

        return clearTableName($this->db('user')->find(array('userid'=>$userid), $field));
	}

}

?>