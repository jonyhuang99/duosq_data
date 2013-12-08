<?php
/**
 * TOP API: taobao.warehouse.item.delete request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:45:40.0
 */
class WarehouseItemDeleteRequest
{
	/** 
	 * 商品ID.可从taobao.warehouse.items.get获得
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
		return "taobao.warehouse.item.delete";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
