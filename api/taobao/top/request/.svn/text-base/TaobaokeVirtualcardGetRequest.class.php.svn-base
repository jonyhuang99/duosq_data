<?php
/**
 * TOP API: taobao.taobaoke.virtualcard.get request
 * 
 * @author auto create
 * @since 1.0, 2010-12-15 16:38:26.0
 */
class TaobaokeVirtualcardGetRequest
{
	/** 
	 * 电话卡地区
	 **/
	private $area;
	
	/** 
	 * 电话卡或游戏点卡标记.
电话卡：phoneCard，游戏卡：gameCard
	 **/
	private $bizType;
	
	/** 
	 * 电话充值卡类型.
卖家代充：autoship，自动发货：autopost，10分钟代充：10minship
	 **/
	private $cardType;
	
	/** 
	 * 需返回的字段列表.可选值:num_iid,title,nick,pic_url,price,click_url,commission,ommission_rate,commission_num,commission_volume,shop_click_url,seller_credit_score,item_location,volume ;字段之间用","分隔
	 **/
	private $fields;
	
	/** 
	 * 游戏名
	 **/
	private $gameName;
	
	/** 
	 * 淘宝用户昵称，注：指的是淘宝的会员登录名.如果昵称错误,那么客户就收不到佣金.每个淘宝昵称都对应于一个pid，在这里输入要结算佣金的淘宝昵称，当推广的商品成功后，佣金会打入此输入的淘宝昵称的账户。具体的信息可以登入阿里妈妈的网站查看
	 **/
	private $nick;
	
	/** 
	 * 电话卡运营商.
1:移动 2:联通 3:电信
	 **/
	private $operator;
	
	/** 
	 * 自定义输入串.格式:英文和数字组成;长度不能大于12个字符,区分不同的推广渠道,如:bbs,表示bbs为推广渠道;blog,表示blog为推广渠道.
	 **/
	private $outerCode;
	
	/** 
	 * 结果页数.1~99
	 **/
	private $pageNo;
	
	/** 
	 * 每页返回结果数.最大每页40
	 **/
	private $pageSize;
	
	/** 
	 * 淘客id
	 **/
	private $pid;
	
	/** 
	 * 充值卡面值
	 **/
	private $price;
	
	private $apiParas = array();
	
	public function setArea($area)
	{
		$this->area = $area;
		$this->apiParas["area"] = $area;
	}

	public function getArea()
	{
		return $this->area;
	}

	public function setBizType($bizType)
	{
		$this->bizType = $bizType;
		$this->apiParas["biz_type"] = $bizType;
	}

	public function getBizType()
	{
		return $this->bizType;
	}

	public function setCardType($cardType)
	{
		$this->cardType = $cardType;
		$this->apiParas["card_type"] = $cardType;
	}

	public function getCardType()
	{
		return $this->cardType;
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

	public function setGameName($gameName)
	{
		$this->gameName = $gameName;
		$this->apiParas["game_name"] = $gameName;
	}

	public function getGameName()
	{
		return $this->gameName;
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

	public function setOperator($operator)
	{
		$this->operator = $operator;
		$this->apiParas["operator"] = $operator;
	}

	public function getOperator()
	{
		return $this->operator;
	}

	public function setOuterCode($outerCode)
	{
		$this->outerCode = $outerCode;
		$this->apiParas["outer_code"] = $outerCode;
	}

	public function getOuterCode()
	{
		return $this->outerCode;
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

	public function setPid($pid)
	{
		$this->pid = $pid;
		$this->apiParas["pid"] = $pid;
	}

	public function getPid()
	{
		return $this->pid;
	}

	public function setPrice($price)
	{
		$this->price = $price;
		$this->apiParas["price"] = $price;
	}

	public function getPrice()
	{
		return $this->price;
	}

	public function getApiMethodName()
	{
		return "taobao.taobaoke.virtualcard.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
