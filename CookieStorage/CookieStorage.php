<?php
class CookieStorage {
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