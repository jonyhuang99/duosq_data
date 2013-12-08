<?php
/**
 * TOP API: taobao.warehouse.item.add request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:42:27.0
 */
class WarehouseItemAddRequest
{
	/** 
	 * 商品名称。最大长度为256字符，一个汉字算两个字符
	 **/
	private $itemName;
	
	/** 
	 * 自定义属性。最大长度为256字符，一个汉字算两个字符
	 **/
	private $memo;
	
	/** 
	 * 商家编码。商家编码唯一，最大长度为64个字符
	 **/
	private $outerId;
	
	private $apiParas = array();
	
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
		return "taobao.warehouse.item.add";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
