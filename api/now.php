<?PHP
//时代互联接口访问底层
namespace API;
require_once "now/sms.inc.php";

class Now extends _Api {

	var $client;

	function __construct(){
		$this->client = new \SMS();
	}

	function sendSms($mobile, $message, $channel='default'){

		$time = time();
		if($channel == 'default'){
			$apitype = 3; // $apitype 通道选择 0：默认通道； 2：通道2； 3：即时通道；
		}else{
			$apitype = 30; // $apitype 通道选择 0：默认通道； 2：通道2； 3：即时通道；
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

		return $succphone;
	}
}

?>