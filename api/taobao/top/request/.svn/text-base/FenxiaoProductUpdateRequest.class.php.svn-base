<?php
/**
 * TOP API: taobao.fenxiao.product.update request
 * 
 * @author auto create
 * @since 1.0, 2010-12-07 14:17:48.0
 */
class FenxiaoProductUpdateRequest
{
	/** 
	 * 警戒库存必须是0到29999。
	 **/
	private $alarmNumber;
	
	/** 
	 * 所在地：市，例：“杭州”
	 **/
	private $city;
	
	/** 
	 * 采购价格，单位：元。例：“10.56”。必须在0.01元到10000000元之间。
	 **/
	private $costPrice;
	
	/** 
	 * 产品描述，长度为5到25000字符。
	 **/
	private $desc;
	
	/** 
	 * 是否有保修，可选值：false（否）、true（是），默认false。
	 **/
	private $haveGuarantee;
	
	/** 
	 * 是否有发票，可选值：false（否）、true（是），默认false。
	 **/
	private $haveInvoice;
	
	/** 
	 * 产品名称，长度不超过60个字节。
	 **/
	private $name;
	
	/** 
	 * 商家编码，长度不能超过60个字节。
	 **/
	private $outerId;
	
	/** 
	 * 产品ID
	 **/
	private $pid;
	
	/** 
	 * ems费用，单位：元。例：“10.56”。大小为0.01元到999999元之间。更新时必须指定运费类型为buyer，否则不更新。
	 **/
	private $postageEms;
	
	/** 
	 * 快递费用，单位：元。例：“10.56”。大小为0.01元到999999元之间。更新时必须指定运费类型为buyer，否则不更新。
	 **/
	private $postageFast;
	
	/** 
	 * 运费模板ID，参考taobao.postages.get。更新时必须指定运费类型为 buyer，否则不更新。
	 **/
	private $postageId;
	
	/** 
	 * 平邮费用，单位：元。例：“10.56”。大小为0.01元到999999元之间。更新时必须指定运费类型为buyer，否则不更新。
	 **/
	private $postageOrdinary;
	
	/** 
	 * 运费类型，可选值：seller（供应商承担运费）、buyer（分销商承担运费），默认seller。
	 **/
	private $postageType;
	
	/** 
	 * 所在地：省，例：“浙江”
	 **/
	private $prov;
	
	/** 
	 * 产品库存必须是1到999999。
	 **/
	private $quantity;
	
	/** 
	 * 最高零售价，单位：元。例：“10.56”。必须在0.01元到10000000元之间，最高零售价必须大于最低零售价。
	 **/
	private $retailPriceHigh;
	
	/** 
	 * 最低零售价，单位：元。例：“10.56”。必须在0.01元到10000000元之间。
	 **/
	private $retailPriceLow;
	
	/** 
	 * sku采购价格，单位元，例："10.50,11.00,20.50"，字段必须和上面的sku_ids保持一致。
	 **/
	private $skuCostPrices;
	
	/** 
	 * sku id列表，例：1001,1002,1003
	 **/
	private $skuIds;
	
	/** 
	 * sku商家编码 ，单位元，例："S1000,S1002,S1003"，字段必须和上面的id保持一致，如果没有可以写成",,"
	 **/
	private $skuOuterIds;
	
	/** 
	 * sku库存，单位元，例："10,20,30"，字段必须和sku_ids保持一致。
	 **/
	private $skuQuantitys;
	
	/** 
	 * 标准价格，单位：元。例：“10.56”。必须在0.01元到10000000元之间。
	 **/
	private $standardPrice;
	
	/** 
	 * 发布状态，可选值：up（上架）、down（下架）、delete（删除），输入非法字符则忽略。
	 **/
	private $status;
	
	private $apiParas = array();
	
	public function setAlarmNumber($alarmNumber)
	{
		$this->alarmNumber = $alarmNumber;
		$this->apiParas["alarm_number"] = $alarmNumber;
	}

	public function getAlarmNumber()
	{
		return $this->alarmNumber;
	}

	public function setCity($city)
	{
		$this->city = $city;
		$this->apiParas["city"] = $city;
	}

	public function getCity()
	{
		return $this->city;
	}

	public function setCostPrice($costPrice)
	{
		$this->costPrice = $costPrice;
		$this->apiParas["cost_price"] = $costPrice;
	}

	public function getCostPrice()
	{
		return $this->costPrice;
	}

	public function setDesc($desc)
	{
		$this->desc = $desc;
		$this->apiParas["desc"] = $desc;
	}

	public function getDesc()
	{
		return $this->desc;
	}

	public function setHaveGuarantee($haveGuarantee)
	{
		$this->haveGuarantee = $haveGuarantee;
		$this->apiParas["have_guarantee"] = $haveGuarantee;
	}

	public function getHaveGuarantee()
	{
		return $this->haveGuarantee;
	}

	public function setHaveInvoice($haveInvoice)
	{
		$this->haveInvoice = $haveInvoice;
		$this->apiParas["have_invoice"] = $haveInvoice;
	}

	public function getHaveInvoice()
	{
		return $this->haveInvoice;
	}

	public function setName($name)
	{
		$this->name = $name;
		$this->apiParas["name"] = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setOuterId($outerId)
	{
		$this->outerId = $outerId;
		$this->apiParas["outer_id"] = $outerId;
	}

	public function getOuterId()
	{
		return $this->outerId;
	}

	public function setPid($pid)
	{
		$this->pid = $pid;
		$this->apiParas["pid"] = $pid;
	}

	public function getPid()
	{
		return $this->pid;
	}

	public function setPostageEms($postageEms)
	{
		$this->postageEms = $postageEms;
		$this->apiParas["postage_ems"] = $postageEms;
	}

	public function getPostageEms()
	{
		return $this->postageEms;
	}

	public function setPostageFast($postageFast)
	{
		$this->postageFast = $postageFast;
		$this->apiParas["postage_fast"] = $postageFast;
	}

	public function getPostageFast()
	{
		return $this->postageFast;
	}

	public function setPostageId($postageId)
	{
		$this->postageId = $postageId;
		$this->apiParas["postage_id"] = $postageId;
	}

	public function getPostageId()
	{
		return $this->postageId;
	}

	public function setPostageOrdinary($postageOrdinary)
	{
		$this->postageOrdinary = $postageOrdinary;
		$this->apiParas["postage_ordinary"] = $postageOrdinary;
	}

	public function getPostageOrdinary()
	{
		return $this->postageOrdinary;
	}

	public function setPostageType($postageType)
	{
		$this->postageType = $postageType;
		$this->apiParas["postage_type"] = $postageType;
	}

	public function getPostageType()
	{
		return $this->postageType;
	}

	public function setProv($prov)
	{
		$this->prov = $prov;
		$this->apiParas["prov"] = $prov;
	}

	public function getProv()
	{
		return $this->prov;
	}

	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;
		$this->apiParas["quantity"] = $quantity;
	}

	public function getQuantity()
	{
		return $this->quantity;
	}

	public function setRetailPriceHigh($retailPriceHigh)
	{
		$this->retailPriceHigh = $retailPriceHigh;
		$this->apiParas["retail_price_high"] = $retailPriceHigh;
	}

	public function getRetailPriceHigh()
	{
		return $this->retailPriceHigh;
	}

	public function setRetailPriceLow($retailPriceLow)
	{
		$this->retailPriceLow = $retailPriceLow;
		$this->apiParas["retail_price_low"] = $retailPriceLow;
	}

	public function getRetailPriceLow()
	{
		return $this->retailPriceLow;
	}

	public function setSkuCostPrices($skuCostPrices)
	{
		$this->skuCostPrices = $skuCostPrices;
		$this->apiParas["sku_cost_prices"] = $skuCostPrices;
	}

	public function getSkuCostPrices()
	{
		return $this->skuCostPrices;
	}

	public function setSkuIds($skuIds)
	{
		$this->skuIds = $skuIds;
		$this->apiParas["sku_ids"] = $skuIds;
	}

	public function getSkuIds()
	{
		return $this->skuIds;
	}

	public function setSkuOuterIds($skuOuterIds)
	{
		$this->skuOuterIds = $skuOuterIds;
		$this->apiParas["sku_outer_ids"] = $skuOuterIds;
	}

	public function getSkuOuterIds()
	{
		return $this->skuOuterIds;
	}

	public function setSkuQuantitys($skuQuantitys)
	{
		$this->skuQuantitys = $skuQuantitys;
		$this->apiParas["sku_quantitys"] = $skuQuantitys;
	}

	public function getSkuQuantitys()
	{
		return $this->skuQuantitys;
	}

	public function setStandardPrice($standardPrice)
	{
		$this->standardPrice = $standardPrice;
		$this->apiParas["standard_price"] = $standardPrice;
	}

	public function getStandardPrice()
	{
		return $this->standardPrice;
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
		return "taobao.fenxiao.product.update";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
