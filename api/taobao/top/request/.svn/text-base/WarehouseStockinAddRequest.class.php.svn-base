<?php
/**
 * TOP API: taobao.warehouse.stockin.add request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:47:21.0
 */
class WarehouseStockinAddRequest
{
	/** 
	 * 预计到达时间（入参形式必须如示例所示）
	 **/
	private $expectCrkDate;
	
	/** 
	 * 要入库的商品的商家编码，以逗号隔开，一次最多500个商品
	 **/
	private $outerIds;
	
	/** 
	 * 要入库的商品的计划入库数量，以逗号隔开，数量必须和outer_ids字段中的out_id数量一致，并且一一对应
	 **/
	private $planCount;
	
	/** 
	 * 每个商品的入库备注，以逗号隔开，和outer_ids中每个outer_id对应。值全为空时可不填，若最少有一条备注，则个数必需与outer_id个数对应，用逗号隔开来表示一条记录。
	 **/
	private $remarks;
	
	/** 
	 * 商家联系人
	 **/
	private $sellerContactor;
	
	/** 
	 * 商家名称
	 **/
	private $sellerName;
	
	/** 
	 * 商家联系电话
	 **/
	private $sellerPhone;
	
	/** 
	 * 配送中心
	 **/
	private $sellerStoreId;
	
	/** 
	 * 总箱数
	 **/
	private $totalPackage;
	
	/** 
	 * 运输公司名称
	 **/
	private $transportName;
	
	/** 
	 * 运输公司运单号
	 **/
	private $transportNo;
	
	private $apiParas = array();
	
	public function setExpectCrkDate($expectCrkDate)
	{
		$this->expectCrkDate = $expectCrkDate;
		$this->apiParas["expect_crk_date"] = $expectCrkDate;
	}

	public function getExpectCrkDate()
	{
		return $this->expectCrkDate;
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
		return "taobao.warehouse.stockin.add";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
