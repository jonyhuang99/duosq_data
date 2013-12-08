<?php
/**
 * TOP API: taobao.koubei.review.search request
 * 
 * @author auto create
 * @since 1.0, 2010-12-02 12:58:34.0
 */
class KoubeiReviewSearchRequest
{
	/** 
	 * 列表翻页用的页码，大于0的整数，默认为1,最大值：50
	 **/
	private $pageNo;
	
	/** 
	 * 每页条数，大于0的整数，默认为20，最大值：80
	 **/
	private $pageSize;
	
	/** 
	 * 评论对象的id
	 **/
	private $targetId;
	
	/** 
	 * 被评论对象的类型，店铺对象类型为“1”,目前就支持这个
	 **/
	private $targetType;
	
	private $apiParas = array();
	
	public function setPageNo($pageNo)
	{
		$this->pageNo = $pageNo;
		$this->apiParas["page_no"] = $pageNo;
	}

	public function getPageNo()
	{
		return $this->pageNo;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
		$this->apiParas["page_size"] = $pageSize;
	}

	public function getPageSize()
	{
		return $this->pageSize;
	}

	public function setTargetId($targetId)
	{
		$this->targetId = $targetId;
		$this->apiParas["target_id"] = $targetId;
	}

	public function getTargetId()
	{
		return $this->targetId;
	}

	public function setTargetType($targetType)
	{
		$this->targetType = $targetType;
		$this->apiParas["target_type"] = $targetType;
	}

	public function getTargetType()
	{
		return $this->targetType;
	}

	public function getApiMethodName()
	{
		return "taobao.koubei.review.search";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
