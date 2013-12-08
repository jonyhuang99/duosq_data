<?php
/**
 * TOP API: taobao.guarantyfunds.get request
 * 
 * @author auto create
 * @since 1.0, 2010-08-23 20:13:33.0
 */
class GuarantyfundsGetRequest
{
	/** 
	 * 查询保证金操作记录创建时间开始，格式为:yyyy-MM-dd
	 **/
	private $beginDate;
	
	/** 
	 * 查询保证金操作记录创建时间结束，格式为:yyyy-MM-dd
	 **/
	private $endDate;
	
	private $apiParas = array();
	
	public function setBeginDate($beginDate)
	{
		$this->beginDate = $beginDate;
		$this->apiParas["begin_date"] = $beginDate;
	}

	public function getBeginDate()
	{
		return $this->beginDate;
	}

	public function setEndDate($endDate)
	{
		$this->endDate = $endDate;
		$this->apiParas["end_date"] = $endDate;
	}

	public function getEndDate()
	{
		return $this->endDate;
	}

	public function getApiMethodName()
	{
		return "taobao.guarantyfunds.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
