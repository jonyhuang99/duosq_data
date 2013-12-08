<?php
/**
 * TOP API: taobao.itempropvalues.get request
 * 
 * @author auto create
 * @since 1.0, 2010-08-12 20:51:05.0
 */
class ItempropvaluesGetRequest
{
	/** 
	 * 叶子类目ID ,通过taobao.itemcats.get获得叶子类目ID
	 **/
	private $cid;
	
	/** 
	 * 假如传2005-01-01 00:00:00，则取所有的属性和子属性(状态为删除的属性值不返回prop_name)
	 **/
	private $datetime;
	
	/** 
	 * 需要返回的字段。目前支持有：cid,pid,prop_name,vid,name,name_alias,status,sort_order
	 **/
	private $fields;
	
	/** 
	 * 属性和属性值 id串，格式例如(pid1;pid2)或(pid1:vid1;pid2:vid2)或(pid1;pid2:vid2)
	 **/
	private $pvs;
	
	private $apiParas = array();
	
	public function setCid($cid)
	{
		$this->cid = $cid;
		$this->apiParas["cid"] = $cid;
	}

	public function getCid()
	{
		return $this->cid;
	}

	public function setDatetime($datetime)
	{
		$this->datetime = $datetime;
		$this->apiParas["datetime"] = $datetime;
	}

	public function getDatetime()
	{
		return $this->datetime;
	}

	public function setFields($fields)
	{
		$this->fields = $fields;
		$this->apiParas["fields"] = $fields;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function setPvs($pvs)
	{
		$this->pvs = $pvs;
		$this->apiParas["pvs"] = $pvs;
	}

	public function getPvs()
	{
		return $this->pvs;
	}

	public function getApiMethodName()
	{
		return "taobao.itempropvalues.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
