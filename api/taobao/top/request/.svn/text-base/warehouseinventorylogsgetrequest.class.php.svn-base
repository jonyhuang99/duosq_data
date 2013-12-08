<?php
/**
 * TOP API: taobao.warehouse.inventorylogs.get request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:48:44.0
 */
class WarehouseInventorylogsGetRequest
{
	/** 
	 * 库存记录id,可从taobao.warehouse.inventories.get获
	 **/
	private $inventoryId;
	
	/** 
	 * 库存变更状态,可选值: UNCONFIRMED(待确认) ,CONFIRMED(已确认)
	 **/
	private $status;
	
	private $apiParas = array();
	
	public function setInventoryId($inventoryId)
	{
		$this->inventoryId = $inventoryId;
		$this->apiParas["inventory_id"] = $inventoryId;
	}

	public function getInventoryId()
	{
		return $this->inventoryId;
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
		return "taobao.warehouse.inventorylogs.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
