<?php
//DAL:验证码模块
namespace DAL;

class Vcode extends _Dal {

	protected $image;
	protected $width = 70;
	protected $height = 22;
	protected $backimg;
	protected $fonts = array(
		'SimSun.ttf',
		'SIMLI.TTF',
		'STXINGKA.TTF'
	);
	protected $verify;
	protected $sourcedir;
	protected $verifyType;

	protected $limit_expire = 3600;
	protected $limit_times = 1;

	function __construct(){

		$this->sourcedir = MYLIBS . 'font' . DS;
	}

	//验证传过来的验证码
	function verify($code){

		if(!$code)return false;
		$mycode = $this->sess('vcode');
		if($mycode == md5(low($code))){
			return true;
		}else{
			return false;
		}
	}

	//判断是否需要验证码
	function need(){

		$ip = getIpByLevel('b');
		if(!$ip){
			setcookie('needvcode', 1, 0, '/'); //用来js判断
			return true;
		}

		$obj = "vcode:ip:{$ip}";

		$times = $this->redis('speed')->sget($obj, $this->limit_expire, $this->limit_times); //每小时限制1次

		if ($this->limit_times <= intval($times)) {
			setcookie('needvcode', 1, 0, '/'); //用来js判断
			return true;
		}
		else {
			setcookie('needvcode', 0, time()-10000000, '/'); //用来js判断
			return false;
		}
	}

	//累计验证码因子
	function record(){

		$ip = getIpByLevel('b');
		if(!$ip)return false;
		$obj = "vcode:ip:{$ip}";

		return $this->redis('speed')->sincr($obj, $this->limit_expire, $this->limit_times);
	}

	function genCode($n = 4) {

		$image = imagecreatetruecolor($this->width, $this->height);
		$col = imagecolorallocate( $image, 255, 255, 255);
		imagefilledrectangle( $image, 0, 0, $this->width, $this->height, $col);
		$this->image = $image;

		$dict = 'ABCDEFGHKLNMPQRSTUVWXYZ23456789';
		$dictlen = strlen($dict);
		$image = $this->image;
		$verify = '';
		$fontfile = $this->sourcedir . $this->fonts[0];
		$colors = array(
			//imagecolorallocate($image, 255, 0, 0), //红
			//imagecolorallocate($image, 0, 0, 255), //蓝
			imagecolorallocate($image, 0, 0, 0), //黑
			imagecolorallocate($image, 0, 0, 0), //黑
			imagecolorallocate($image, 0, 0, 0), //黑
		);


		for ($i = 0; $i < $n; $i++) {
			$x = 12;
			$y = 21;

			$verify.= $code = substr($dict, mt_rand(0, $dictlen - 1), 1);

			$angle = 25;
			$space = $i * 12 + $x;
			if($i == 1 || $i == 3){
				$angle = -25;
				$space = $space - 8;
				$y = 17;
			}

			imagettftext($image, 20, $angle, $space, $y, $colors[array_rand($colors)], $fontfile, $code);
		}
		$this->verify = $verify;
		return $this;
	}

	/**
	 * 加噪点
	 */
	function addNoise($n = 50) {
		$image = $this->image;
		$color = imagecolorallocate($image, 0, 0, 0);
		for ($i = 0; $i < $n; $i++) { //噪声点
			imagesetpixel($image, mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
		}
		return $this;
	}

	/**
	 * 加噪音线
	 */
	function addLine($n = 1) {
		$image = $this->image;
		$color = imagecolorallocate($image, 0, 0, 0);
		for ($i = 0; $i < $n; $i++) {
			imagearc($image, rand(-10, $this->width + 10), rand(-10, 0), rand($this->width * 2 + 10, $this->width * 2 + 40), rand($this->height, $this->height + 20), 0, 360, $color);
		}
		return $this;
	}

	function display(){

		ob_start();

		imagepng($this->image);
		$imgstring = ob_get_contents();
		ob_end_clean();

		header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');

		header('Content-type: image/png');
		header('Content-Length: '.strlen($imgstring));
		echo $imgstring;
		imagedestroy($this->image);
		imagedestroy($this->backimg);

		// 记录session
		$this->sess('vcode', md5(low($this->verify)));

		return $this->verify;
	}
}
?>