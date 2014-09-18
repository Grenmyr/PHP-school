<?php
class CookieStorage {
	//Stolen code since my code didn't want to work at first
	//The code came from: https://github.com/dntoll/1dv408-HT14/blob/master/Like/src/CookieStorage.php
	//I feel that it is justified since I wrote all other code for this and this is so simple
	
	private static $cookieName = "CookieStorage";

	public function save($string) {
		setcookie( self::$cookieName, $string, -1);

		//var_dump($_COOKIE);
		//die();
	}

	public function load() {

		//$ret = isset($_COOKIE["CookieStorage"]) ? $_COOKIE["CookieStorage"] : "";
		if (isset($_COOKIE[self::$cookieName]))
			$ret = $_COOKIE[self::$cookieName];
		else
			$ret = "";

		setcookie(self::$cookieName, "", time() -1);

		return $ret;
	}
}