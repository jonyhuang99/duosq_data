<?php
/**
 * TOP API: taobao.koubei.coupon.search request
 * 
 * @author auto create
 * @since 1.0, 2010-12-02 12:57:11.0
 */
class KoubeiCouponSearchRequest
{
	/** 
	 * 口碑城市代码，可参见口碑全国各城市的对照表
	 **/
	private $cityId;
	
	/** 
	 * 搜索用的排序字段，暂时只支持publish_time,offer_hits.升降序规则是排序字段加英文逗号加DESC或ASC
比如：offer_hits,DESC
	 **/
	private $orderBy;
	
	/** 
	 * 页码.传入值为1代表第一页,传入值为2代表第二页,依此类推.默认返回的数据是从第一页开始
	 **/
	private $pageNo;
	
	/** 
	 * 每页数量默认10条，最大1000条
	 **/
	private $pageSize;
	
	/** 
	 * 搜索商圈的范围
	 **/
	private $periRange;
	
	/** 
	 * 搜索用的关键字
	 **/
	private $q;
	
	/** 
	 * 搜索用是否支持短信下载,如果使用该参数即使赋值空在内部处理时也会以false值进行查询
	 **/
	private $smsDown;
	
	/** 
	 * 搜索用店的id
	 **/
	private $storeId;
	
	/** 
	 * 优惠券子类目，比如10123,10124,10125,10126,10127
	 **/
	private $subCate;
	
	/** 
	 * 经度,已经将经度的分秒转换成度的小数部分,最好精确到小数点后5位
	 **/
	private $x;
	
	/** 
	 * 纬度，已经将纬度的分秒转换成度的小数部分，最好精确到小数点后5位
	 **/
	private $y;
	
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

	public function setOrderBy($orderBy)
	{
		$this->orderBy = $orderBy;
		$this->apiParas["order_by"] = $orderBy;
	}

	public function getOrderBy()
	{
		return $this->orderBy;
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

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
		$this->apiParas["page_size"] = $pageSize;
	}

	public function getPageSize()
	{
		return $this->pageSize;
	}

	public function setPeriRange($periRange)
	{
		$this->periRange = $periRange;
		$this->apiParas["peri_range"] = $periRange;
	}

	public function getPeriRange()
	{
		return $this->periRange;
	}

	public function setQ($q)
	{
		$this->q = $q;
		$this->apiParas["q"] = $q;
	}

	public function getQ()
	{
		return $this->q;
	}

	public function setSmsDown($smsDown)
	{
		$this->smsDown = $smsDown;
		$this->apiParas["sms_down"] = $smsDown;
	}

	public function getSmsDown()
	{
		return $this->smsDown;
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

	public function setSubCate($subCate)
	{
		$this->subCate = $subCate;
		$this->apiParas["sub_cate"] = $subCate;
	}

	public function getSubCate()
	{
		return $this->subCate;
	}

	public function setX($x)
	{
		$this->x = $x;
		$this->apiParas["x"] = $x;
	}

	public function getX()
	{
		return $this->x;
	}

	public function setY($y)
	{
		$this->y = $y;
		$this->apiParas["y"] = $y;
	}

	public function getY()
	{
		return $this->y;
	}

	public function getApiMethodName()
	{
		return "taobao.koubei.coupon.search";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
