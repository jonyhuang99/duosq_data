<?php
/**
 * TOP API: taobao.warehouse.sellerstores.get request
 * 
 * @author auto create
 * @since 1.0, 2010-10-21 08:46:27.0
 */
class WarehouseSellerstoresGetRequest
{
	/** 
	 * 自定义仓库状态，如果不传，则返回所有
	 **/
	private $status;
	
	private $apiParas = array();
	
	public function setStatus($status)
	{
		$this->status = $status;
		$this->apiParas["status"] = $status;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function getApiMethodName()
	{
		return "taobao.warehouse.sellerstores.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
