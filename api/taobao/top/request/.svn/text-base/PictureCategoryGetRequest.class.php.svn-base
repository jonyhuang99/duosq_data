<?php
/**
 * TOP API: taobao.picture.category.get request
 * 
 * @author auto create
 * @since 1.0, 2010-11-23 17:07:42.0
 */
class PictureCategoryGetRequest
{
	/** 
	 * 需要返回的字段,根据PictureCategory中的以下字段：picture_category_id,picture_category_name,position,type,total,created,modified ,多个字段用“,”分隔。如：type,total,created,modified
	 **/
	private $fields;
	
	/** 
	 * 图片分类ID
	 **/
	private $pictureCategoryId;
	
	/** 
	 * 图片分类名，不支持模糊查询
	 **/
	private $pictureCategoryName;
	
	/** 
	 * 分类类型,fixed代表店铺装修分类类别，auction代表宝贝分类类别，user-define代表用户自定义分类类别
	 **/
	private $type;
	
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

	public function setPictureCategoryId($pictureCategoryId)
	{
		$this->pictureCategoryId = $pictureCategoryId;
		$this->apiParas["picture_category_id"] = $pictureCategoryId;
	}

	public function getPictureCategoryId()
	{
		return $this->pictureCategoryId;
	}

	public function setPictureCategoryName($pictureCategoryName)
	{
		$this->pictureCategoryName = $pictureCategoryName;
		$this->apiParas["picture_category_name"] = $pictureCategoryName;
	}

	public function getPictureCategoryName()
	{
		return $this->pictureCategoryName;
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
		return "taobao.picture.category.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
