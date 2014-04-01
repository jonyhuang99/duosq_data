<?PHP
//时代互联接口访问底层
namespace API;
require_once "now/sms.inc.php";

class Now extends _Api {

	var $client;

	function __construct(){
		$this->client = new \SMS();
	}

	/**
	 * 通道列表：
	 * 3 - 通道二（通知类）(发送1条扣去1条)
	 * 33 - 通道三(发送1条扣去1条)
	 * 2 - 即时通道二（营销类）(发送1条扣去1.3条)
	 * 9 - 即时通道三（通知验证）(发送1条扣去1.3条)
	 * 10 - 促销通道(发送1条扣去1.3条)
	 * 10 - 促销通道(发送1条扣去1.3条)
	 * 30 - 正规短信(可回复)(发送1条扣去1.3条)
	 */
	function sendSms($mobile, $message, $channel='default'){

		$time = time();
		if($channel == 'default'){
			$apitype = 3;
		}else{
			$apitype = 3;
		}

		$respxml=$this->client->sendSMS($mobile, g2u($message, true), $time, $apitype);
		$_SESSION["xml"]=$this->client->sendXML;
		$_SESSION["respxml"]=$respxml;
		$code=$this->client->getCode();
		$respArr=$this->client->toArray();
		$mess=$respArr["msg"];
		$smsid=$respArr["idmessage"][0];
		$succnum=$respArr["successnum"][0];
		$succphone=$respArr["successphone"][0];
		$failephone=$respArr["failephone"][0];
		$status=$respArr["smsstatus"][0];

		writeLog('sms', 'send', '['.$mobile.']['.$channel.']['.$message.'][status:'.$status.'][succ:'.$succphone.']');
		return $succphone;
	}
}

?>