<?php
/**
 * TOP API: taobao.warehouse.item.update request
 * 
 * @author auto create
 * @since 1.0, 2010-12-10 11:52:43.0
 */
class WarehouseItemUpdateRequest
{
	/** 
	 * 商品Id.可从taobao.warehouse.items.get处获得
	 **/
	private $itemId;
	
	/** 
	 * 商品名称，不能超过256字符,一个汉字算两个字符
	 **/
	private $itemName;
	
	/** 
	 * 自定义属性。不能超过256字符，一个汉字算两个字符
	 **/
	private $memo;
	
	/** 
	 * 该字段已作废。商家编码。和卖家发布商品的商家编码保持一致。商家编码必须唯一，且不能超过64字符，一个汉字算两个字符
	 **/
	private $outerId;
	
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

	public function setItemName($itemName)
	{
		$this->itemName = $itemName;
		$this->apiParas["item_name"] = $itemName;
	}

	public function getItemName()
	{
		return $this->itemName;
	}

	public function setMemo($memo)
	{
		$this->memo = $memo;
		$this->apiParas["memo"] = $memo;
	}

	public function getMemo()
	{
		return $this->memo;
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

	public function getApiMethodName()
	{
		return "taobao.warehouse.item.update";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
