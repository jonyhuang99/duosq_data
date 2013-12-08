<?php
/**
 * TOP API: taobao.warehouse.order.get request
 * 
 * @author auto create
 * @since 1.0, 2010-11-23 17:19:15.0
 */
class WarehouseOrderGetRequest
{
	/** 
	 * 物流订单ID
	 **/
	private $orderId;
	
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

	public function getApiMethodName()
	{
		return "taobao.warehouse.order.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
