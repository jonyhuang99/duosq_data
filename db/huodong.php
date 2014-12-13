<?php
//活动数据操作基类
namespace DB;

class Huodong extends _Db {

	var $name = 'Huodong';

	//活动参与状态
	const STATUS_WAIT = 0; //状态_待激活
	const STATUS_PASS = 1; //状态_通过
	const STATUS_INVALID = 2; //状态_无效

	//活动类型
	const TYPE_NEW = 1; //新人下单满减活动
}
?>