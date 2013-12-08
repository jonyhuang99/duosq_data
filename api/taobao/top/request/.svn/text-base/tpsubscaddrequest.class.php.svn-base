<?php
/**
 * TOP API: taobao.tp.subsc.add request
 * 
 * @author auto create
 * @since 1.0, 2010-10-14 11:51:12.0
 */
class TpSubscAddRequest
{
	/** 
	 * 用户昵称
	 **/
	private $nick;
	
	/** 
	 * 订单结束时间
	 **/
	private $orderEndTime;
	
	/** 
	 * 订单开始时间,此时间必须大于等于当前时间
	 **/
	private $orderStartTime;
	
	/** 
	 * 外部交易订单号
	 **/
	private $outTradeNo;
	
	private $apiParas = array();
	
	public function setNick($nick)
	{
		$this->nick = $nick;
		$this->apiParas["nick"] = $nick;
	}

	public function getNick()
	{
		return $this->nick;
	}

	public function setOrderEndTime($orderEndTime)
	{
		$this->orderEndTime = $orderEndTime;
		$this->apiParas["order_end_time"] = $orderEndTime;
	}

	public function getOrderEndTime()
	{
		return $this->orderEndTime;
	}

	public function setOrderStartTime($orderStartTime)
	{
		$this->orderStartTime = $orderStartTime;
		$this->apiParas["order_start_time"] = $orderStartTime;
	}

	public function getOrderStartTime()
	{
		return $this->orderStartTime;
	}

	public function setOutTradeNo($outTradeNo)
	{
		$this->outTradeNo = $outTradeNo;
		$this->apiParas["out_trade_no"] = $outTradeNo;
	}

	public function getOutTradeNo()
	{
		return $this->outTradeNo;
	}

	public function getApiMethodName()
	{
		return "taobao.tp.subsc.add";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
