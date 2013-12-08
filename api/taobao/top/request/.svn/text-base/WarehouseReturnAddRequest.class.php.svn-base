<?php
/**
 * TOP API: taobao.warehouse.return.add request
 * 
 * @author auto create
 * @since 1.0, 2010-12-20 10:21:23.0
 */
class WarehouseReturnAddRequest
{
	/** 
	 * 买家地址
	 **/
	private $buyerAddress;
	
	/** 
	 * 买家联系人
	 **/
	private $buyerContactor;
	
	/** 
	 * 买家联系电话。格式：纯数字(手机) 或者 区号-数字
	 **/
	private $buyerPhone;
	
	/** 
	 * 联系方式,只能输入如下值：
CONTACT_MODE_STORE --  配送中心联系
CONTACT_MODE_DEALER --  买家联系
	 **/
	private $contactExpressMode;
	
	/** 
	 * 服务方式，只能输入如下值(英文):
OPTIONS_STORE --  配送中心处理
OPTIONS_DEALER --  商家处理
	 **/
	private $options;
	
	/** 
	 * 原物流编号，与“原订单编号”至少选填一个
	 **/
	private $orderCode;
	
	/** 
	 * 要退货的商品的商家编码，以逗号隔开
	 **/
	private $outerIds;
	
	/** 
	 * 计划退货数量，以逗号隔开，个数与商家编码一致。
	 **/
	private $planCount;
	
	/** 
	 * 备注,个数与商家编码一致，以逗号隔开。若中间无值也需以占一个值. 例"test1,,test3".若全为空则不需要给值
	 **/
	private $remarks;
	
	/** 
	 * 原因描述
	 **/
	private $returnReason;
	
	/** 
	 * 退货原因,只能输入如下值(英文)：
REASON_QUALITY -- 商品质量问题
REASON_UNCONFORMITY -- 收到的商品不符
REASON_REBATE  -- 折扣、赠品、发票问题
REASON_HEBDOMAD  -- 7天无理由退货
REASON_OTHER  -- 其他
	 **/
	private $returnReasonCode;
	
	/** 
	 * 商家联系人
	 **/
	private $sellerContactor;
	
	/** 
	 * 商家联系电话。格式：纯数字(手机) 或者 区号-数字
	 **/
	private $sellerPhone;
	
	/** 
	 * 配送中心，若服务方式为配送中心处理，则该字段必填，反之则不填
	 **/
	private $sellerStoreId;
	
	/** 
	 * 原订单编号，与“原物流编号”至少选填一个。
	 **/
	private $taobaoTradeId;
	
	/** 
	 * 物流公司
	 **/
	private $transportName;
	
	/** 
	 * 运单号码,若联系方式选择买家联系，则该字段必填，反之则不填。说明：退货快递公司运单上的运单号
	 **/
	private $transportNo;
	
	private $apiParas = array();
	
	public function setBuyerAddress($buyerAddress)
	{
		$this->buyerAddress = $buyerAddress;
		$this->apiParas["buyer_address"] = $buyerAddress;
	}

	public function getBuyerAddress()
	{
		return $this->buyerAddress;
	}

	public function setBuyerContactor($buyerContactor)
	{
		$this->buyerContactor = $buyerContactor;
		$this->apiParas["buyer_contactor"] = $buyerContactor;
	}

	public function getBuyerContactor()
	{
		return $this->buyerContactor;
	}

	public function setBuyerPhone($buyerPhone)
	{
		$this->buyerPhone = $buyerPhone;
		$this->apiParas["buyer_phone"] = $buyerPhone;
	}

	public function getBuyerPhone()
	{
		return $this->buyerPhone;
	}

	public function setContactExpressMode($contactExpressMode)
	{
		$this->contactExpressMode = $contactExpressMode;
		$this->apiParas["contact_express_mode"] = $contactExpressMode;
	}

	public function getContactExpressMode()
	{
		return $this->contactExpressMode;
	}

	public function setOptions($options)
	{
		$this->options = $options;
		$this->apiParas["options"] = $options;
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function setOrderCode($orderCode)
	{
		$this->orderCode = $orderCode;
		$this->apiParas["order_code"] = $orderCode;
	}

	public function getOrderCode()
	{
		return $this->orderCode;
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

	public function setReturnReason($returnReason)
	{
		$this->returnReason = $returnReason;
		$this->apiParas["return_reason"] = $returnReason;
	}

	public function getReturnReason()
	{
		return $this->returnReason;
	}

	public function setReturnReasonCode($returnReasonCode)
	{
		$this->returnReasonCode = $returnReasonCode;
		$this->apiParas["return_reason_code"] = $returnReasonCode;
	}

	public function getReturnReasonCode()
	{
		return $this->returnReasonCode;
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

	public function setTaobaoTradeId($taobaoTradeId)
	{
		$this->taobaoTradeId = $taobaoTradeId;
		$this->apiParas["taobao_trade_id"] = $taobaoTradeId;
	}

	public function getTaobaoTradeId()
	{
		return $this->taobaoTradeId;
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
		return "taobao.warehouse.return.add";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
