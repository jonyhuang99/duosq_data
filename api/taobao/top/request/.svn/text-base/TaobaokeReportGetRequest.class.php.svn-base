<?php
/**
 * TOP API: taobao.taobaoke.report.get request
 * 
 * @author auto create
 * @since 1.0, 2010-08-12 20:52:43.0
 */
class TaobaokeReportGetRequest
{
	/** 
	 * 需要查询报表的日期，有效的日期为最近3个月内的某一天，格式为:yyyyMMdd,如20090520.
	 **/
	private $date;
	
	/** 
	 * 需返回的字段列表.可选值:TaobaokeReportMember淘宝客报表成员结构体中的所有字段;字段之间用","分隔.
	 **/
	private $fields;
	
	private $apiParas = array();
	
	public function setDate($date)
	{
		$this->date = $date;
		$this->apiParas["date"] = $date;
	}

	public function getDate()
	{
		return $this->date;
	}

	public function setFields($fields)
	{
		$this->fields = $fields;
		$this->apiParas["fields"] = $fields;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function getApiMethodName()
	{
		return "taobao.taobaoke.report.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
