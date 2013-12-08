<?php
/**
 * TOP API: taobao.traderates.get request
 * 
 * @author auto create
 * @since 1.0, 2010-09-27 09:29:39.0
 */
class TraderatesGetRequest
{
	/** 
	 * 需返回的字段列表。可选值：TradeRate 结构中的所有字段，多个字段之间用“,”分隔
	 **/
	private $fields;
	
	/** 
	 * 页码。取值范围:大于零的整数; 默认值:1
	 **/
	private $pageNo;
	
	/** 
	 * 每页条数。取值范围:大于零的整数; 默认值:40;最大值:200
	 **/
	private $pageSize;
	
	/** 
	 * 评价类型。可选值:get(得到),give(给出)
	 **/
	private $rateType;
	
	/** 
	 * 评价结果。可选值:good(好评),neutral(中评),bad(差评)
	 **/
	private $result;
	
	/** 
	 * 评价者角色。可选值:seller(卖家),buyer(买家)
	 **/
	private $role;
	
	private $apiParas = array();
	
	public function setFields($fields)
	{
		$this->fields = $fields;
		$this->apiParas["fields"] = $fields;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function setPageNo($pageNo)
	{
		$this->pageNo = $pageNo;
		$this->apiParas["page_no"] = $pageNo;
	}

	public function getPageNo()
	{
		return $this->pageNo;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
		$this->apiParas["page_size"] = $pageSize;
	}

	public function getPageSize()
	{
		return $this->pageSize;
	}

	public function setRateType($rateType)
	{
		$this->rateType = $rateType;
		$this->apiParas["rate_type"] = $rateType;
	}

	public function getRateType()
	{
		return $this->rateType;
	}

	public function setResult($result)
	{
		$this->result = $result;
		$this->apiParas["result"] = $result;
	}

	public function getResult()
	{
		return $this->result;
	}

	public function setRole($role)
	{
		$this->role = $role;
		$this->apiParas["role"] = $role;
	}

	public function getRole()
	{
		return $this->role;
	}

	public function getApiMethodName()
	{
		return "taobao.traderates.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
