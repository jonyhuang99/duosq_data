<?php
/**
 * TOP API: taobao.warehouse.inventory.get request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:50:31.0
 */
class WarehouseInventoryGetRequest
{
	/** 
	 * 库存ID (可从taobao.warehouse.inventories.get取)
	 **/
	private $inventoryId;
	
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

	public function getApiMethodName()
	{
		return "taobao.warehouse.inventory.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
