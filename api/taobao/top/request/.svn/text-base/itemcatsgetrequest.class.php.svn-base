<?php
/**
 * TOP API: taobao.itemcats.get request
 * 
 * @author auto create
 * @since 1.0, 2010-08-27 10:17:00.0
 */
class ItemcatsGetRequest
{
	/** 
	 * 商品所属类目ID列表，用半角逗号(,)分隔 例如:(18957,19562,) (cids、parent_cid至少传一个)
	 **/
	private $cids;
	
	/** 
	 * 时间戳。格式:yyyy-MM-dd HH:mm:ss
如果没有传，则取所有的类目信息(如果传了parent_cid或者cids，则忽略datetime)
	 **/
	private $datetime;
	
	/** 
	 * 需要返回的字段列表，见ItemCat，默认返回：cid,parent_cid,name,is_parent
	 **/
	private $fields;
	
	/** 
	 * 父商品类目 id，0表示根节点, 传输该参数返回所有子类目。 (cids、parent_cid至少传一个)
	 **/
	private $parentCid;
	
	private $apiParas = array();
	
	public function setCids($cids)
	{
		$this->cids = $cids;
		$this->apiParas["cids"] = $cids;
	}

	public function getCids()
	{
		return $this->cids;
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

	public function setParentCid($parentCid)
	{
		$this->parentCid = $parentCid;
		$this->apiParas["parent_cid"] = $parentCid;
	}

	public function getParentCid()
	{
		return $this->parentCid;
	}

	public function getApiMethodName()
	{
		return "taobao.itemcats.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
