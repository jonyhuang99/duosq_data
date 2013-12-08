<?php
/**
 * TOP API: taobao.koubei.city.tocity request
 * 
 * @author auto create
 * @since 1.0, 2010-12-02 12:56:35.0
 */
class KoubeiCityTocityRequest
{
	/** 
	 * 口碑的城市ID,优先根据ID取城市，再根据name取
	 **/
	private $id;
	
	/** 
	 * 城市名
	 **/
	private $name;
	
	private $apiParas = array();
	
	public function setId($id)
	{
		$this->id = $id;
		$this->apiParas["id"] = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setName($name)
	{
		$this->name = $name;
		$this->apiParas["name"] = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getApiMethodName()
	{
		return "taobao.koubei.city.tocity";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
