<?php
/**
 * TOP API: taobao.warehouse.inventorylogs.page.get request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:49:08.0
 */
class WarehouseInventorylogsPageGetRequest
{
	/** 
	 * 查询结束时间
	 **/
	private $gmtcreateEnd;
	
	/** 
	 * 查询开始时间
	 **/
	private $gmtcreateStart;
	
	/** 
	 * 分页参数,当前页码,默认为1
	 **/
	private $pageNo;
	
	/** 
	 * 分页参数,每页所包含的记录条数,正整数，默认20，最大50超过50默认50
	 **/
	private $pageSize;
	
	/** 
	 * 库存变更状态PUT_IN_STOCK(入库),STOCK_TRANSFER(出库) ，STOCKTAKING（盘点）,SELL（正常销售）
	 **/
	private $reason;
	
	/** 
	 * 自定义仓库id 可以根据taobao.warehouse.sellerstores.get接口得到
	 **/
	private $sellerStoreId;
	
	/** 
	 * 库存变更状态UNCONFIRMED(待确认),CONFIRMED(已确认)
	 **/
	private $status;
	
	private $apiParas = array();
	
	public function setGmtcreateEnd($gmtcreateEnd)
	{
		$this->gmtcreateEnd = $gmtcreateEnd;
		$this->apiParas["gmtcreate_end"] = $gmtcreateEnd;
	}

	public function getGmtcreateEnd()
	{
		return $this->gmtcreateEnd;
	}

	public function setGmtcreateStart($gmtcreateStart)
	{
		$this->gmtcreateStart = $gmtcreateStart;
		$this->apiParas["gmtcreate_start"] = $gmtcreateStart;
	}

	public function getGmtcreateStart()
	{
		return $this->gmtcreateStart;
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

	public function setReason($reason)
	{
		$this->reason = $reason;
		$this->apiParas["reason"] = $reason;
	}

	public function getReason()
	{
		return $this->reason;
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

	public function setStatus($status)
	{
		$this->status = $status;
		$this->apiParas["status"] = $status;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.inventorylogs.page.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
