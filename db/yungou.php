<?php
//云购商品数据操作基类
namespace DB;

class Yungou extends _Db {

	var $name = 'Yungou';

	//云购商品状态
	const STATUS_WAIT_OPEN = 0; //状态_待开放
	const STATUS_OPENING = 1; //状态_开放购买
	const STATUS_SELECTING = 2; //状态_开奖中
	const STATUS_FINISH = 3; //状态_已开奖
	const STATUS_FAILED = 4; //状态_已失败
}
?>