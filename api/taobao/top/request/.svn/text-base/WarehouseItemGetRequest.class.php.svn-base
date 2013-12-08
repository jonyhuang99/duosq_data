<?php
/**
 * TOP API: taobao.warehouse.item.get request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:45:23.0
 */
class WarehouseItemGetRequest
{
	/** 
	 * 商品id。可从taobao.warehouse.items.get获得。当type="item_id"时，根据输入的item_id查询，默认为0
	 **/
	private $itemId;
	
	/** 
	 * 商家编码.和卖家发布商品的商家编码一致,可从taobao.warehouse.items.get获得。当type="outer_id"时,根据输入的outer_id查询，默认为""
	 **/
	private $outerId;
	
	/** 
	 * 参数类型。必须传入"item_id"（根据商品Id查商品信息）或"outer_id"（根据商家编码查询商品信息）
	 **/
	private $type;
	
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

	public function setOuterId($outerId)
	{
		$this->outerId = $outerId;
		$this->apiParas["outer_id"] = $outerId;
	}

	public function getOuterId()
	{
		return $this->outerId;
	}

	public function setType($type)
	{
		$this->type = $type;
		$this->apiParas["type"] = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.item.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
