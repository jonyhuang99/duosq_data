<?php
/**
 * TOP API: taobao.warehouse.storebill.page.get request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:43:51.0
 */
class WarehouseStorebillPageGetRequest
{
	/** 
	 * 结束月份,格式为年+月,如:201001
	 **/
	private $monthEnd;
	
	/** 
	 * 起始月份,格式为年+月,如:200912
	 **/
	private $monthStart;
	
	/** 
	 * 当前页，正整数,默认为1.
	 **/
	private $pageNo;
	
	/** 
	 * 分页记录个数，正整数,默认为20条,最多为50条，如果用户输入的记录数大于50，则一页显示50条记录
	 **/
	private $pageSize;
	
	/** 
	 * 自定义仓库id,可以从接口taobao.warehouse.sellerstores.get中得到
	 **/
	private $sellerStoreId;
	
	private $apiParas = array();
	
	public function setMonthEnd($monthEnd)
	{
		$this->monthEnd = $monthEnd;
		$this->apiParas["month_end"] = $monthEnd;
	}

	public function getMonthEnd()
	{
		return $this->monthEnd;
	}

	public function setMonthStart($monthStart)
	{
		$this->monthStart = $monthStart;
		$this->apiParas["month_start"] = $monthStart;
	}

	public function getMonthStart()
	{
		return $this->monthStart;
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

	public function setSellerStoreId($sellerStoreId)
	{
		$this->sellerStoreId = $sellerStoreId;
		$this->apiParas["seller_store_id"] = $sellerStoreId;
	}

	public function getSellerStoreId()
	{
		return $this->sellerStoreId;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.storebill.page.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
