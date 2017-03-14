<?php
error_reporting(E_ALL);

class V2ex {
	const COOKIE = '/home/wwwroot/default/v2ex.cookie';
	const USERAGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
	const REFERER = 'https://www.v2ex.com/signin';
	const LOGIN_URL = 'https://www.v2ex.com/signin';
	const LOGIN_ACTION_URL = 'https://www.v2ex.com/signin';
	const MISSION_DAILY_URL = 'https://v2ex.com/mission/daily';
	//const MISSION_DAILY_URL='https://v2ex.com/signin?next=/mission/daily';
	const MISSION_DAILY_ACTION_URL = 'https://v2ex.com/mission/daily/redeem';
	private $u;
	private $p;
	private $cookie;

	private $isLogin = false;

	public static function init($u, $p, $cookie = self::COOKIE) {
		$v2ex = new V2ex();
		$v2ex->u = $u;
		$v2ex->p = $p;
		$v2ex->cookie = $cookie;
		$v2ex->login();
		return $v2ex;
	}

	public function login() {
		$html = $this->loginPage();
		$user_code = $this->getUserNameCode($html);
		$pwd_code = $this->getPasswordCode($html);
		$login_code = $this->getLoginCode($html);
		$params = array(
			'next' => '/',
			$user_code => $this->u,
			$pwd_code => $this->p,
			'once' => $login_code,
		);
		$this->send(self::LOGIN_ACTION_URL, "POST", $params);
		//$result = $this->send('https://v2ex.com');
		echo "\n=========== login params ================\n";
		echo "= login_user_key : " . $user_code . "\n";
		echo "= login_pswd_key : " . $pwd_code . "\n";
		echo "= login_once_key : " . $login_code . "\n";
		echo "==========================================\n";
		$this->isLogin = true;
	}

	private function loginPage() {
		return $this->send(self::LOGIN_URL);
	}

	private function missionDailyPage() {
		return $this->send(self::MISSION_DAILY_URL);
	}

	public function missionDaily() {
		if ($this->isLogin !== true) {
			$this->login();
		}
		$html = $this->missionDailyPage();
		$code = $this->getMissionCode($html);
		if (empty($code)) {
			echo "\n无法找到MissionCode 或者 已经领取过了\n\n\n";
			return;
		}
		echo "\n========= misson code (" . $code . ") =========\n";
		$url = self::MISSION_DAILY_ACTION_URL . "?once=" . $code;
		echo date('Y-m-d H:i:s') . " mission url:" . $url . "\n";
		echo "\n\n";
		$this->send($url);
	}

	private function getUserNameCode($html) {
		$pattern = '/input\stype="text"\sclass="sl"\sname="(\w{64})"\svalue=/iu';
		if (preg_match($pattern, $html, $matchs)) {
			$code = $matchs[1];
		}
		return !empty($code) ? $code : false;
	}

	private function getPasswordCode($html) {
		$code = false;
		$pattern = '/input\stype="password"\sclass="sl"\sname="(\w{64})"\svalue=/iu';
		if (preg_match($pattern, $html, $matchs)) {
			$code = @$matchs[1];
		}
		return $code;
	}

	private function getLoginCode($html) {
		$code = false;
		$pattern = '/input\stype="hidden"\svalue="(\w{5})"\sname="once"/iu';
		if (preg_match($pattern, $html, $matchs)) {
			$code = @$matchs[1];
		}
		return $code;
	}

	private function getMissionCode($html) {
		$code = false;
		$pattern = '/mission\/daily\/redeem\?once=(\w{5})/iu';
		if (preg_match($pattern, $html, $matchs)) {
			$code = @$matchs[1];
		}
		return $code;
	}

	private function send($url, $type = 'GET', $params = false) {
		$ch = curl_init(); //初始化

		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_STDERR, fopen('php://output', 'w'));
		curl_setopt($ch, CURLOPT_URL, $url); // 要访问的地址
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
		curl_setopt($ch, CURLOPT_HEADER, false); //不返回header部分
		curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //返回字符串，而非直接输出
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //跟随跳转
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie); //发送cookies
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie); //存储cookies
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		if ($type === "POST") {
			//curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 使用自动跳转
		}

		if (!empty($params) && is_array($params)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
			curl_setopt($ch, CURLOPT_REFERER, self::REFERER);
		} else {
			curl_setopt($ch, CURLOPT_REFERER, self::MISSION_DAILY_URL);
		}

		$html = curl_exec($ch);
		//echo "=================================\n";
		//var_dump($html);
		//调试使用
		if ($html === FALSE) {
			echo "cURL Error: " . curl_error($ch);
		}
		curl_close($ch);

		return $html;
	}

}

$v2ex = V2ex::init("username", "password");
//var_dump($v2ex);
$v2ex->missionDaily();
