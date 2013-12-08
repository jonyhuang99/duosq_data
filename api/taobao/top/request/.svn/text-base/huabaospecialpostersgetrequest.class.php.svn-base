<?php
/**
 * TOP API: taobao.huabao.specialposters.get request
 * 
 * @author auto create
 * @since 1.0, 2010-11-26 09:44:04.0
 */
class HuabaoSpecialpostersGetRequest
{
	/** 
	 * 频道Id
	 **/
	private $channelIds;
	
	/** 
	 * 返回的记录数，默认10条，最多20条，如果请求超过20或者小于等于0，则按10条返回
	 **/
	private $number;
	
	/** 
	 * 类型可选：HOT(热门），RECOMMEND（推荐）
	 **/
	private $type;
	
	private $apiParas = array();
	
	public function setChannelIds($channelIds)
	{
		$this->channelIds = $channelIds;
		$this->apiParas["channel_ids"] = $channelIds;
	}

	public function getChannelIds()
	{
		return $this->channelIds;
	}

	public function setNumber($number)
	{
		$this->number = $number;
		$this->apiParas["number"] = $number;
	}

	public function getNumber()
	{
		return $this->number;
	}

	public function setType($type)
	{
		$this->type = $type;
		$this->apiParas["type"] = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getApiMethodName()
	{
		return "taobao.huabao.specialposters.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
