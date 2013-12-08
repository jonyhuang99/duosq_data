<?php
/**
 * TOP API: taobao.warehouse.order.cancel request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:47:54.0
 */
class WarehouseOrderCancelRequest
{
	/** 
	 * 订单id，order_id可以根据接口taobao.warehouse.orders.page.get得到
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
		return "taobao.warehouse.order.cancel";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
