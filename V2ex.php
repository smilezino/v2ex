<?php

class V2ex {
	const COOKIE='/data/v2ex.cookie';
	const USERAGENT='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
	const REFERER = 'https://v2ex.com/signin';
	const LOGIN_URL='https://v2ex.com/signin';
	const LOGIN_ACTION_URL='https://v2ex.com/signin';
	const MISSION_DAILY_URL='https://v2ex.com/mission/daily';
	const MISSION_DAILY_ACTION_URL='https://v2ex.com/mission/daily/redeem';
	private $u;
	private $p;
	private $cookie;
	
	private $isLogin;
	
	public static function init($u, $p, $cookie=self::COOKIE) {
		$v2ex = new V2ex();
		$v2ex->u = $u;
		$v2ex->p = $p;
		$v2ex->cookie = $cookie;
		$v2ex->login();
		return $v2ex;
	}
	
	public function login() {
		$html = $this->loginPage();
		$params = array(
			'u'=>$this->u,
			'p'=>$this->p,
			'once'=>$this->getLoginCode($html),
			'next'=>'/'
		);
		$v2ex = $this->send(self::LOGIN_ACTION_URL, "POST", $params);
		$this->isLogin = true;
	}
	
	private function loginPage() {
		return $this->send(self::LOGIN_URL);
	}
	
	private function missionDailyPage() {
		return $this->send(self::MISSION_DAILY_URL);
	}
	
	public function missionDaily() {
		if($this->isLogin!==true) {
			$this->login();
		}
		$html = $this->missionDailyPage();
		$code = $this->getMissionCode($html);
		if(empty($code)){
			return ;
		}
		$url = self::MISSION_DAILY_ACTION_URL."?once=".$code;
		echo date('Y-m-d H:i:s')." mission url:".$url."\n";
		$this->send($url);
	}
	
	private function getLoginCode($html) {
		$pattern = '/input\stype="hidden"\svalue="(\w{5})"\sname="once"/iu';
		if(preg_match($pattern, $html, $matchs)) {
			$code = $matchs[1];
		}
		return !empty($code)?$code:false;
	}
	
	private function getMissionCode($html) {
		$pattern = '/mission\/daily\/redeem\?once=(\w{5})/iu';
		if(preg_match($pattern, $html, $matchs)) {
			$code = $matchs[1];
		}
		return !empty($code)?$code:false;
	}
	
	private function send($url, $type='GET', $params=false) {
		$ch = curl_init($url); //初始化
		curl_setopt($ch, CURLOPT_HEADER, 0); //不返回header部分
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串，而非直接输出
		curl_setopt($ch, CURLOPT_COOKIEFILE,  $this->cookie); //发送cookies
		curl_setopt($ch, CURLOPT_COOKIEJAR,  $this->cookie); //存储cookies
		if($type==="POST") {
			curl_setopt($ch, CURLOPT_POST, 1);
		}
		if(!empty($params) && is_array($params)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_REFERER, self::REFERER);
		} else {
			curl_setopt($ch, CURLOPT_REFERER, self::MISSION_DAILY_URL);
		}
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
		$html = curl_exec($ch);
		//调试使用
		if ($html === FALSE) {
			echo "cURL Error: " . curl_error($ch);
		}
		curl_close($ch);
		return $html;
	}
}

$v2ex = V2ex::init("username", "password");
$v2ex->missionDaily();
