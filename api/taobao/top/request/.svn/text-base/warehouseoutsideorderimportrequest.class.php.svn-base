<?php
/**
 * TOP API: taobao.warehouse.outside.order.import request
 * 
 * @author auto create
 * @since 1.0, 2010-12-20 10:20:06.0
 */
class WarehouseOutsideOrderImportRequest
{
	/** 
	 * 自动发货标识,ENABLED(自动检查发货规则，如果满足自动发货，则自动发货)和DISABLED(不采用自动发货),默认为DISABLED
	 **/
	private $autoConsign;
	
	/** 
	 * json格式的货物列表，形式如：{"goods_list":[{"goods_name":"name","single_price":"100","goods_quantity":"10","outer_id":"abcd"},{"goods_name":"name1","single_price":"101","goods_quantity":"11","outer_id":"abcd1"}]}其中只有outer_id是可选的，outer_id可以根据接口taobao.warehouse.items.get得到
	 **/
	private $goodsListJson;
	
	/** 
	 * 货物备注（长度不大于125字符）
	 **/
	private $memo;
	
	/** 
	 * 收货人详细地址（长度在4-60字符之间）
	 **/
	private $receiverAddress;
	
	/** 
	 * 中国地区编码值(必须为地区叶子节点)如"110101"(北京市市辖区东城区)，获取参见taobao.areas.get
	 **/
	private $receiverCountryId;
	
	/** 
	 * 收货人手机号
	 **/
	private $receiverMobilePhone;
	
	/** 
	 * 收货人姓名（长度不大于20字节）
	 **/
	private $receiverName;
	
	/** 
	 * 收货人电话号
	 **/
	private $receiverTelephone;
	
	/** 
	 * 收货人邮编
	 **/
	private $receiverZipCode;
	
	private $apiParas = array();
	
	public function setAutoConsign($autoConsign)
	{
		$this->autoConsign = $autoConsign;
		$this->apiParas["auto_consign"] = $autoConsign;
	}

	public function getAutoConsign()
	{
		return $this->autoConsign;
	}

	public function setGoodsListJson($goodsListJson)
	{
		$this->goodsListJson = $goodsListJson;
		$this->apiParas["goods_list_json"] = $goodsListJson;
	}

	public function getGoodsListJson()
	{
		return $this->goodsListJson;
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

	public function setReceiverAddress($receiverAddress)
	{
		$this->receiverAddress = $receiverAddress;
		$this->apiParas["receiver_address"] = $receiverAddress;
	}

	public function getReceiverAddress()
	{
		return $this->receiverAddress;
	}

	public function setReceiverCountryId($receiverCountryId)
	{
		$this->receiverCountryId = $receiverCountryId;
		$this->apiParas["receiver_country_id"] = $receiverCountryId;
	}

	public function getReceiverCountryId()
	{
		return $this->receiverCountryId;
	}

	public function setReceiverMobilePhone($receiverMobilePhone)
	{
		$this->receiverMobilePhone = $receiverMobilePhone;
		$this->apiParas["receiver_mobile_phone"] = $receiverMobilePhone;
	}

	public function getReceiverMobilePhone()
	{
		return $this->receiverMobilePhone;
	}

	public function setReceiverName($receiverName)
	{
		$this->receiverName = $receiverName;
		$this->apiParas["receiver_name"] = $receiverName;
	}

	public function getReceiverName()
	{
		return $this->receiverName;
	}

	public function setReceiverTelephone($receiverTelephone)
	{
		$this->receiverTelephone = $receiverTelephone;
		$this->apiParas["receiver_telephone"] = $receiverTelephone;
	}

	public function getReceiverTelephone()
	{
		return $this->receiverTelephone;
	}

	public function setReceiverZipCode($receiverZipCode)
	{
		$this->receiverZipCode = $receiverZipCode;
		$this->apiParas["receiver_zip_code"] = $receiverZipCode;
	}

	public function getReceiverZipCode()
	{
		return $this->receiverZipCode;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.outside.order.import";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
