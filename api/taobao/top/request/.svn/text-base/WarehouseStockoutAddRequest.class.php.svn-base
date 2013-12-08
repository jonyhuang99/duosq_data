<?php
/**
 * TOP API: taobao.warehouse.stockout.add request
 * 
 * @author auto create
 * @since 1.0, 2010-12-08 17:02:08.0
 */
class WarehouseStockoutAddRequest
{
	/** 
	 * 运输公司联系人身份证号
	 **/
	private $cardId;
	
	/** 
	 * 出库目的地
	 **/
	private $destination;
	
	/** 
	 * 预计出库时间
	 **/
	private $exceptCrkDate;
	
	/** 
	 * 要出库的商品的商家编码，以逗号隔开，一次最多500个商品
	 **/
	private $outerIds;
	
	/** 
	 * 要出库的商品的计划出库数量，以逗号隔开，数量必须要和outer_ids字段中的outer_id数量一致，并且一一对应。
	 **/
	private $planCount;
	
	/** 
	 * 每个商品的出库备注，以逗号隔开，和outer_ids中每个outer_id对应。值全为空时可不填，若最少有一条备注，则个数必需与outer_id个数对应，用逗号隔开来表示一条记录。
	 **/
	private $remarks;
	
	/** 
	 * 商家联系人
	 **/
	private $sellerContactor;
	
	/** 
	 * 当前入库单卖家名称
	 **/
	private $sellerName;
	
	/** 
	 * 商家联系电话
	 **/
	private $sellerPhone;
	
	/** 
	 * 自定义仓库id，可从taobao.warehouse.sellerstores.get接口得到
	 **/
	private $sellerStoreId;
	
	/** 
	 * 总箱数
	 **/
	private $totalPackage;
	
	/** 
	 * 运输公司联系人
	 **/
	private $transportContactor;
	
	/** 
	 * 运输公司联系人联系电话
	 **/
	private $transportContactorPhone;
	
	/** 
	 * 运输公司名称
	 **/
	private $transportName;
	
	/** 
	 * 运输公司运单号
	 **/
	private $transportNo;
	
	private $apiParas = array();
	
	public function setCardId($cardId)
	{
		$this->cardId = $cardId;
		$this->apiParas["card_id"] = $cardId;
	}

	public function getCardId()
	{
		return $this->cardId;
	}

	public function setDestination($destination)
	{
		$this->destination = $destination;
		$this->apiParas["destination"] = $destination;
	}

	public function getDestination()
	{
		return $this->destination;
	}

	public function setExceptCrkDate($exceptCrkDate)
	{
		$this->exceptCrkDate = $exceptCrkDate;
		$this->apiParas["except_crk_date"] = $exceptCrkDate;
	}

	public function getExceptCrkDate()
	{
		return $this->exceptCrkDate;
	}

	public function setOuterIds($outerIds)
	{
		$this->outerIds = $outerIds;
		$this->apiParas["outer_ids"] = $outerIds;
	}

	public function getOuterIds()
	{
		return $this->outerIds;
	}

	public function setPlanCount($planCount)
	{
		$this->planCount = $planCount;
		$this->apiParas["plan_count"] = $planCount;
	}

	public function getPlanCount()
	{
		return $this->planCount;
	}

	public function setRemarks($remarks)
	{
		$this->remarks = $remarks;
		$this->apiParas["remarks"] = $remarks;
	}

	public function getRemarks()
	{
		return $this->remarks;
	}

	public function setSellerContactor($sellerContactor)
	{
		$this->sellerContactor = $sellerContactor;
		$this->apiParas["seller_contactor"] = $sellerContactor;
	}

	public function getSellerContactor()
	{
		return $this->sellerContactor;
	}

	public function setSellerName($sellerName)
	{
		$this->sellerName = $sellerName;
		$this->apiParas["seller_name"] = $sellerName;
	}

	public function getSellerName()
	{
		return $this->sellerName;
	}

	public function setSellerPhone($sellerPhone)
	{
		$this->sellerPhone = $sellerPhone;
		$this->apiParas["seller_phone"] = $sellerPhone;
	}

	public function getSellerPhone()
	{
		return $this->sellerPhone;
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

	public function setTotalPackage($totalPackage)
	{
		$this->totalPackage = $totalPackage;
		$this->apiParas["total_package"] = $totalPackage;
	}

	public function getTotalPackage()
	{
		return $this->totalPackage;
	}

	public function setTransportContactor($transportContactor)
	{
		$this->transportContactor = $transportContactor;
		$this->apiParas["transport_contactor"] = $transportContactor;
	}

	public function getTransportContactor()
	{
		return $this->transportContactor;
	}

	public function setTransportContactorPhone($transportContactorPhone)
	{
		$this->transportContactorPhone = $transportContactorPhone;
		$this->apiParas["transport_contactor_phone"] = $transportContactorPhone;
	}

	public function getTransportContactorPhone()
	{
		return $this->transportContactorPhone;
	}

	public function setTransportName($transportName)
	{
		$this->transportName = $transportName;
		$this->apiParas["transport_name"] = $transportName;
	}

	public function getTransportName()
	{
		return $this->transportName;
	}

	public function setTransportNo($transportNo)
	{
		$this->transportNo = $transportNo;
		$this->apiParas["transport_no"] = $transportNo;
	}

	public function getTransportNo()
	{
		return $this->transportNo;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.stockout.add";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
