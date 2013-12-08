<?php
/**
 * TOP API: taobao.warehouse.inventorylog.confirm request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:43:06.0
 */
class WarehouseInventorylogConfirmRequest
{
	/** 
	 * 库存变更id，可以根据接口taobao.warehouse.inventorylogs.page.get得到
	 **/
	private $inventoryLogId;
	
	private $apiParas = array();
	
	public function setInventoryLogId($inventoryLogId)
	{
		$this->inventoryLogId = $inventoryLogId;
		$this->apiParas["inventory_log_id"] = $inventoryLogId;
	}

	public function getInventoryLogId()
	{
		return $this->inventoryLogId;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.inventorylog.confirm";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
