<?php
//用户待跟单订单表管理
namespace DB;

class OrderWaitTrace extends _Db {

	var $name = 'OrderWaitTrace';

	//处理状态
	const STATUS_WAIT_TRACE = 0; //等待跟单
	const STATUS_SUCC = 1; //已跟单
	const STATUS_ERROR_NOT_EXIST = 2; //无该订单
	const STATUS_ERROR_BUYDATE_INVALID = 3; //非2015订单
}
?>