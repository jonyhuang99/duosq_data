<?php
/**
 * TOP API: taobao.warehouse.orders.page.get request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:45:01.0
 */
class WarehouseOrdersPageGetRequest
{
	/** 
	 * 物流公司ID
	 **/
	private $companyId;
	
	/** 
	 * 订单创建结束时间
	 **/
	private $gmtcreateEnd;
	
	/** 
	 * 订单创建开始时间
	 **/
	private $gmtcreateStart;
	
	/** 
	 * 物流订单状态TO_BE_SENT（待处理）SIGN_IN（已签收）REJECTED_BY_OTHER_SIDE(签收失败)
	 **/
	private $orderStatus;
	
	/** 
	 * 运单号
	 **/
	private $outSid;
	
	/** 
	 * 当前页 (默认为1)
	 **/
	private $pageNo;
	
	/** 
	 * 分页参数,每页所包含的记录条数,正整数，默认20，最大50超过50默认50
	 **/
	private $pageSize;
	
	/** 
	 * 收货人名字
	 **/
	private $receiverName;
	
	/** 
	 * 自定义仓库ID
	 **/
	private $sellerStoreId;
	
	/** 
	 * 淘宝交易号
	 **/
	private $tid;
	
	private $apiParas = array();
	
	public function setCompanyId($companyId)
	{
		$this->companyId = $companyId;
		$this->apiParas["company_id"] = $companyId;
	}

	public function getCompanyId()
	{
		return $this->companyId;
	}

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

	public function setOrderStatus($orderStatus)
	{
		$this->orderStatus = $orderStatus;
		$this->apiParas["order_status"] = $orderStatus;
	}

	public function getOrderStatus()
	{
		return $this->orderStatus;
	}

	public function setOutSid($outSid)
	{
		$this->outSid = $outSid;
		$this->apiParas["out_sid"] = $outSid;
	}

	public function getOutSid()
	{
		return $this->outSid;
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

	public function setReceiverName($receiverName)
	{
		$this->receiverName = $receiverName;
		$this->apiParas["receiver_name"] = $receiverName;
	}

	public function getReceiverName()
	{
		return $this->receiverName;
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

	public function setTid($tid)
	{
		$this->tid = $tid;
		$this->apiParas["tid"] = $tid;
	}

	public function getTid()
	{
		return $this->tid;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.orders.page.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
