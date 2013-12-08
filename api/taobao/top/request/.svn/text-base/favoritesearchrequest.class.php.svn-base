<?php
/**
 * TOP API: taobao.favorite.search request
 * 
 * @author auto create
 * @since 1.0, 2010-09-20 18:32:42.0
 */
class FavoriteSearchRequest
{
	/** 
	 * ITEM表示宝贝，SHOP表示商铺，collect_type只能为这两者之一
	 **/
	private $collectType;
	
	/** 
	 * 页码。取值范围:大于零的整数; 默认值:1。一页20条记录。
	 **/
	private $pageNo;
	
	/** 
	 * 用户昵称
	 **/
	private $userNick;
	
	private $apiParas = array();
	
	public function setCollectType($collectType)
	{
		$this->collectType = $collectType;
		$this->apiParas["collect_type"] = $collectType;
	}

	public function getCollectType()
	{
		return $this->collectType;
	}

	public function setPageNo($pageNo)
	{
		$this->pageNo = $pageNo;
		$this->apiParas["page_no"] = $pageNo;
	}

	public function getPageNo()
	{
		return $this->pageNo;
	}

	public function setUserNick($userNick)
	{
		$this->userNick = $userNick;
		$this->apiParas["user_nick"] = $userNick;
	}

	public function getUserNick()
	{
		return $this->userNick;
	}

	public function getApiMethodName()
	{
		return "taobao.favorite.search";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
