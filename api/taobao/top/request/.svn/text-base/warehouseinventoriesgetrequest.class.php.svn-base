<?php
/**
 * TOP API: taobao.warehouse.inventories.get request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:50:15.0
 */
class WarehouseInventoriesGetRequest
{
	/** 
	 * 商品ID(可从taobao.warehouse.items.get取
	 **/
	private $itemId;
	
	private $apiParas = array();
	
	public function setItemId($itemId)
	{
		$this->itemId = $itemId;
		$this->apiParas["item_id"] = $itemId;
	}

	public function getItemId()
	{
		return $this->itemId;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.inventories.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
