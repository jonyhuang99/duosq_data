<?php
/**
 * TOP API: taobao.postage.get request
 * 
 * @author auto create
 * @since 1.0, 2010-08-04 18:43:05.0
 */
class PostageGetRequest
{
	/** 
	 * 需返回的字段列表.可选值:Postage结构体中的所有字段;字段之间用","隔开
	 **/
	private $fields;
	
	/** 
	 * 卖家昵称
	 **/
	private $nick;
	
	/** 
	 * 邮费模板id
	 **/
	private $postageId;
	
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

	public function setNick($nick)
	{
		$this->nick = $nick;
		$this->apiParas["nick"] = $nick;
	}

	public function getNick()
	{
		return $this->nick;
	}

	public function setPostageId($postageId)
	{
		$this->postageId = $postageId;
		$this->apiParas["postage_id"] = $postageId;
	}

	public function getPostageId()
	{
		return $this->postageId;
	}

	public function getApiMethodName()
	{
		return "taobao.postage.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
