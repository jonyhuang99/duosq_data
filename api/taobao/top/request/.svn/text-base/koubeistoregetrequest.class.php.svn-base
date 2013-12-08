<?php
/**
 * TOP API: taobao.koubei.store.get request
 * 
 * @author auto create
 * @since 1.0, 2010-12-02 12:57:34.0
 */
class KoubeiStoreGetRequest
{
	/** 
	 * 城市id
	 **/
	private $cityId;
	
	/** 
	 * 店铺id
	 **/
	private $storeId;
	
	private $apiParas = array();
	
	public function setCityId($cityId)
	{
		$this->cityId = $cityId;
		$this->apiParas["city_id"] = $cityId;
	}

	public function getCityId()
	{
		return $this->cityId;
	}

	public function setStoreId($storeId)
	{
		$this->storeId = $storeId;
		$this->apiParas["store_id"] = $storeId;
	}

	public function getStoreId()
	{
		return $this->storeId;
	}

	public function getApiMethodName()
	{
		return "taobao.koubei.store.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
