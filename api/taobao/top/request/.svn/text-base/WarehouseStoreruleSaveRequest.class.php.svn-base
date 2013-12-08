<?php
/**
 * TOP API: taobao.warehouse.storerule.save request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:44:10.0
 */
class WarehouseStoreruleSaveRequest
{
	/** 
	 * 发货规则中的备用仓库
	 **/
	private $backSellerStoreId;
	
	/** 
	 * 仓库覆盖地区代码.如"310000,320000"代表"上海 江苏".地区代码可调用 taobao.areas.get 获取.
	 **/
	private $provAreaIds;
	
	/** 
	 * 要设置发货规则的自定义仓库的id,可调用 taobao.warehouse.sellerstores.get 获取.
	 **/
	private $sellerStoreId;
	
	private $apiParas = array();
	
	public function setBackSellerStoreId($backSellerStoreId)
	{
		$this->backSellerStoreId = $backSellerStoreId;
		$this->apiParas["back_seller_store_id"] = $backSellerStoreId;
	}

	public function getBackSellerStoreId()
	{
		return $this->backSellerStoreId;
	}

	public function setProvAreaIds($provAreaIds)
	{
		$this->provAreaIds = $provAreaIds;
		$this->apiParas["prov_area_ids"] = $provAreaIds;
	}

	public function getProvAreaIds()
	{
		return $this->provAreaIds;
	}

	public function setSellerStoreId($sellerStoreId)
	{
		$this->sellerStoreId = $sellerStoreId;
		$this->apiParas["seller_store_id"] = $sellerStoreId;
	}

	public function getSellerStoreId()
	{
		return $this->sellerStoreId;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.storerule.save";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
