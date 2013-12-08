<?php
/**
 * TOP API: taobao.fenxiao.orders.get request
 * 
 * @author auto create
 * @since 1.0, 2010-08-10 11:56:33.0
 */
class FenxiaoOrdersGetRequest
{
	/** 
	 * 结束时间 格式 yyyy-MM-dd 起始时间与结束时间跨度为7天 。
	 **/
	private $endCreated;
	
	/** 
	 * 页码。（大于0的整数。默认为1）
	 **/
	private $pageNo;
	
	/** 
	 * 每页条数。（每页条数不超过50条）
	 **/
	private $pageSize;
	
	/** 
	 * 采购单编号
	 **/
	private $purchaseOrderId;
	
	/** 
	 * 起始时间 格式 yyyy-MM-dd。
	 **/
	private $startCreated;
	
	/** 
	 * 交易状态，不传默认查询所有采购单。可选值:

    * WAIT_BUYER_PAY(等待买家付款)
    * WAIT_SELLER_SEND_GOODS(等待卖家发货,即:买家已付款)
    * WAIT_BUYER_CONFIRM_GOODS(等待买家确认收货,即:卖家已发货)
    * TRADE_FINISHED(交易成功)
    * TRADE_CLOSED(交易关闭)
	 **/
	private $status;
	
	/** 
	 * 可选值：trade_time_type(采购单按照成交时间范围查询),update_time_type(采购单按照更新时间范围查询)
	 **/
	private $timeType;
	
	private $apiParas = array();
	
	public function setEndCreated($endCreated)
	{
		$this->endCreated = $endCreated;
		$this->apiParas["end_created"] = $endCreated;
	}

	public function getEndCreated()
	{
		return $this->endCreated;
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

	public function setPurchaseOrderId($purchaseOrderId)
	{
		$this->purchaseOrderId = $purchaseOrderId;
		$this->apiParas["purchase_order_id"] = $purchaseOrderId;
	}

	public function getPurchaseOrderId()
	{
		return $this->purchaseOrderId;
	}

	public function setStartCreated($startCreated)
	{
		$this->startCreated = $startCreated;
		$this->apiParas["start_created"] = $startCreated;
	}

	public function getStartCreated()
	{
		return $this->startCreated;
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

	public function setTimeType($timeType)
	{
		$this->timeType = $timeType;
		$this->apiParas["time_type"] = $timeType;
	}

	public function getTimeType()
	{
		return $this->timeType;
	}

	public function getApiMethodName()
	{
		return "taobao.fenxiao.orders.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
