<?php
/**
 * TOP API: taobao.warehouse.order.storeConsign request
 * 
 * @author auto create
 * @since 1.0, 2010-12-08 17:02:24.0
 */
class WarehouseOrderStoreConsignRequest
{
	/** 
	 * 订单id
	 **/
	private $orderId;
	
	/** 
	 * 发货仓储 id 可以从接口taobao.warehouse.sellerstores.get中得到
	 **/
	private $sellerStoreId;
	
	private $apiParas = array();
	
	public function setOrderId($orderId)
	{
		$this->orderId = $orderId;
		$this->apiParas["order_id"] = $orderId;
	}

	public function getOrderId()
	{
		return $this->orderId;
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
		return "taobao.warehouse.order.storeConsign";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
