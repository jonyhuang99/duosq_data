<?php
/**
 * TOP API: taobao.increment.authorizemessages.get request
 * 
 * @author auto create
 * @since 1.0, 2010-12-08 14:09:35.0
 */
class IncrementAuthorizemessagesGetRequest
{
	/** 
	 * 用户授权剩余时间过滤。以天计算，取值范围为0-7之间的整数，0表示当天会过期的用户。例如：传入3，表示查询从当天开始，未来3天内会过期的用户授权信息。
	 **/
	private $expiredDay;
	
	/** 
	 * 需要返回的字段。具体字段间AuthorizeMessage说明
	 **/
	private $fields;
	
	/** 
	 * 用户昵称列表，每个nick之间以","间隔，一次不超过20个
	 **/
	private $nicks;
	
	/** 
	 * 页码。取值范围:大于零的整数; 默认值:1,即返回第一页数据。
	 **/
	private $pageNo;
	
	/** 
	 * 每页条数。取值范围:大于零的整数;最大值:200;默认值:40。注：只有不指定nick参数时分页才有作用。
	 **/
	private $pageSize;
	
	private $apiParas = array();
	
	public function setExpiredDay($expiredDay)
	{
		$this->expiredDay = $expiredDay;
		$this->apiParas["expired_day"] = $expiredDay;
	}

	public function getExpiredDay()
	{
		return $this->expiredDay;
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

	public function setNicks($nicks)
	{
		$this->nicks = $nicks;
		$this->apiParas["nicks"] = $nicks;
	}

	public function getNicks()
	{
		return $this->nicks;
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

	public function getApiMethodName()
	{
		return "taobao.increment.authorizemessages.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
