<?php
/**
 * TOP API: taobao.delivery.send request
 * 
 * @author auto create
 * @since 1.0, 2010-11-11 19:49:44.0
 */
class DeliverySendRequest
{
	/** 
	 * 物流公司代码.如"POST"就代表中国邮政,"ZJS"就代表宅急送.调用 taobao.logistics.companies.get 获取。如传入的代码非淘宝官方物流合作公司，默认是“其他”物流的方式，在淘宝不显示物流具体进度，故传入需谨慎。如果orderType为delivery_needed，则必传
	 **/
	private $companyCode;
	
	/** 
	 * 返回的字段
	 **/
	private $fields;
	
	/** 
	 * 卖家备注.最大长度为250个字符。如果orderType为delivery_needed，则必传
	 **/
	private $memo;
	
	/** 
	 * 发货类型. 可选( delivery_needed(物流订单发货),virtual_goods(虚拟物品发货). ) 注:选择virtual_goods类型进行发货的话下面的参数可以不需填写。如果选择delivery_needed 则company_code,out_sid,seller_name,seller_area_id,seller_address,seller_zip,seller_phone,seller_mobile,memo必须要填写
	 **/
	private $orderType;
	
	/** 
	 * 运单号.具体一个物流公司的真实运单号码。淘宝官方物流会校验，请谨慎传入；若company_code中传入的代码非淘宝官方物流合作公司，此处运单号不校验。如果orderType为delivery_needed，则必传
	 **/
	private $outSid;
	
	/** 
	 * 卖家地址(详细地址).如:XXX街道XXX门牌,省市区不需要提供。如果orderType为delivery_needed，则必传
	 **/
	private $sellerAddress;
	
	/** 
	 * 卖家所在地国家公布的标准地区码.参考:http://www.stats.gov.cn/tjbz/xzqhdm/t20080215_402462675.htm  或者调用 taobao.areas.get 获取。如果orderType为delivery_needed，则必传
	 **/
	private $sellerAreaId;
	
	/** 
	 * 卖家手机号码，必须由8到16位数字构成
	 **/
	private $sellerMobile;
	
	/** 
	 * 卖家姓名。如果orderType为delivery_needed，则必传
	 **/
	private $sellerName;
	
	/** 
	 * 卖家固定电话.包含区号,电话,分机号,中间用 " – "; 卖家固定电话和卖家手机号码,必须填写一个.
	 **/
	private $sellerPhone;
	
	/** 
	 * 卖家邮编。如果orderType为delivery_needed，则必传
	 **/
	private $sellerZip;
	
	/** 
	 * 交易ID
	 **/
	private $tid;
	
	private $apiParas = array();
	
	public function setCompanyCode($companyCode)
	{
		$this->companyCode = $companyCode;
		$this->apiParas["company_code"] = $companyCode;
	}

	public function getCompanyCode()
	{
		return $this->companyCode;
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

	public function setMemo($memo)
	{
		$this->memo = $memo;
		$this->apiParas["memo"] = $memo;
	}

	public function getMemo()
	{
		return $this->memo;
	}

	public function setOrderType($orderType)
	{
		$this->orderType = $orderType;
		$this->apiParas["order_type"] = $orderType;
	}

	public function getOrderType()
	{
		return $this->orderType;
	}

	public function setOutSid($outSid)
	{
		$this->outSid = $outSid;
		$this->apiParas["out_sid"] = $outSid;
	}

	public function getOutSid()
	{
		return $this->outSid;
	}

	public function setSellerAddress($sellerAddress)
	{
		$this->sellerAddress = $sellerAddress;
		$this->apiParas["seller_address"] = $sellerAddress;
	}

	public function getSellerAddress()
	{
		return $this->sellerAddress;
	}

	public function setSellerAreaId($sellerAreaId)
	{
		$this->sellerAreaId = $sellerAreaId;
		$this->apiParas["seller_area_id"] = $sellerAreaId;
	}

	public function getSellerAreaId()
	{
		return $this->sellerAreaId;
	}

	public function setSellerMobile($sellerMobile)
	{
		$this->sellerMobile = $sellerMobile;
		$this->apiParas["seller_mobile"] = $sellerMobile;
	}

	public function getSellerMobile()
	{
		return $this->sellerMobile;
	}

	public function setSellerName($sellerName)
	{
		$this->sellerName = $sellerName;
		$this->apiParas["seller_name"] = $sellerName;
	}

	public function getSellerName()
	{
		return $this->sellerName;
	}

	public function setSellerPhone($sellerPhone)
	{
		$this->sellerPhone = $sellerPhone;
		$this->apiParas["seller_phone"] = $sellerPhone;
	}

	public function getSellerPhone()
	{
		return $this->sellerPhone;
	}

	public function setSellerZip($sellerZip)
	{
		$this->sellerZip = $sellerZip;
		$this->apiParas["seller_zip"] = $sellerZip;
	}

	public function getSellerZip()
	{
		return $this->sellerZip;
	}

	public function setTid($tid)
	{
		$this->tid = $tid;
		$this->apiParas["tid"] = $tid;
	}

	public function getTid()
	{
		return $this->tid;
	}

	public function getApiMethodName()
	{
		return "taobao.delivery.send";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
