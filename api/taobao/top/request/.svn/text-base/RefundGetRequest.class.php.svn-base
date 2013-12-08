<?php
/**
 * TOP API: taobao.refund.get request
 * 
 * @author auto create
 * @since 1.0, 2010-08-03 15:20:42.0
 */
class RefundGetRequest
{
	/** 
	 * 需要返回的字段。目前支持有：refund_id, alipay_no, tid, oid, buyer_nick, seller_nick, total_fee, status, created, refund_fee, good_status, has_good_return, payment, reason, desc, num_iid, title, price, num, good_return_time, company_name, sid, address, shipping_type, refund_remind_timeout
	 **/
	private $fields;
	
	/** 
	 * 退款单号
	 **/
	private $refundId;
	
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

	public function setRefundId($refundId)
	{
		$this->refundId = $refundId;
		$this->apiParas["refund_id"] = $refundId;
	}

	public function getRefundId()
	{
		return $this->refundId;
	}

	public function getApiMethodName()
	{
		return "taobao.refund.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
