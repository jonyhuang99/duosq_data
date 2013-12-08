<?php
/**
 * TOP API: taobao.warehouse.inventory.warnline.update request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:44:26.0
 */
class WarehouseInventoryWarnlineUpdateRequest
{
	/** 
	 * 库存id列表，形式如"111,222,333,444"的库存id列表，可以根据taobao.warehouse.inventories.get接口得到,每个id必须为数值型
	 **/
	private $inventoryIds;
	
	/** 
	 * 库存警戒值列表,形式如"11，22，33，44"的警戒值列表，每个id必须为数值型
	 **/
	private $warnValues;
	
	private $apiParas = array();
	
	public function setInventoryIds($inventoryIds)
	{
		$this->inventoryIds = $inventoryIds;
		$this->apiParas["inventory_ids"] = $inventoryIds;
	}

	public function getInventoryIds()
	{
		return $this->inventoryIds;
	}

	public function setWarnValues($warnValues)
	{
		$this->warnValues = $warnValues;
		$this->apiParas["warn_values"] = $warnValues;
	}

	public function getWarnValues()
	{
		return $this->warnValues;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.inventory.warnline.update";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
