<?php
/**
 * TOP API: taobao.item.skus.get request
 * 
 * @author auto create
 * @since 1.0, 2010-12-14 15:08:57.0
 */
class ItemSkusGetRequest
{
	/** 
	 * 需返回的字段列表。可选值：Sku结构体中的所有字段；字段之间用“,”分隔。
	 **/
	private $fields;
	
	/** 
	 * sku所属商品数字id，　必选
	 **/
	private $numIids;
	
	private $apiParas = array();
	
	public function setFields($fields)
	{
		$this->fields = $fields;
		$this->apiParas["fields"] = $fields;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function setNumIids($numIids)
	{
		$this->numIids = $numIids;
		$this->apiParas["num_iids"] = $numIids;
	}

	public function getNumIids()
	{
		return $this->numIids;
	}

	public function getApiMethodName()
	{
		return "taobao.item.skus.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
