<?php
//推广任务数据操作
namespace DB;

class Advtask extends _Db {

	var $name = 'Advtask';

	//推广任务表状态定义
	const STATUS_WAIT_REVIEW = 0;
	const STATUS_PASS = 1;
	const STATUS_INVALID = 2;
	const STATUS_ANSWER_WAIT_REVIEW = 4;
	const STATUS_ANSWER_PASS = 5;

	//推广任务类型定义
	const TYPE_WENDA_ASK = 1;
	const TYPE_WENDA_ANSWER = 2;
	const TYPE_TIEBA = 3;
	const TYPE_BBS = 4;
	const TYPE_ZHUANKE = 5;

	//推广任务被删状态
	const DELETED_WAIT_REVIEW = 0;
	const DELETED_YES = 1;
	const DELETED_NO = 2;
}
?>