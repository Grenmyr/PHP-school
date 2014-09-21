<?php
require_once("CookieStorage/CookieStorage.php");

class LoginView{
	private $model;
	private $message;
	private $username;
	private $password;
	private $messages;

	function __construct(UserModel $model, CookieStorage $cookieStorage, $username){
		$this->model = $model;
		$this->messages = $cookieStorage;
	}
	private function getLogoutForm(){
		$logoutFormHtml = "	<form method='post'>
								<input type='hidden' name='logout'>
								<input type='submit' value='Logout'>
							</form>";

		return $logoutFormHtml;
	}
	private function getMessage(){
		$this->message = $this->messages->load();
		$getMessageHtml = "<p>{$this->message}</p>";
		return $getMessageHtml;
	}
	/*
	* Login form with the username spliced in there if we need it to be, thanks PHP for being so odd and allowing these things to work
	*/
	private function getLoginForm(){
		$_SESSION["username"] = isset($_SESSION["username"]) ? $_SESSION["username"] : "";
		$this->username = $_SESSION["username"];
		$_SESSION["username"] = "";

		$loginFormHtml = "<form method='post'>
						Username
						<input type='text' name='username'" . (($this->username) ? "value={$this->username}>": ">") . "Password
						<input type='password' name='password'>
						<input name='rememberme' type='checkbox'>
						<input type='submit' value='Submit'>
					</form>";

			return $loginFormHtml;
	}
	/*
	* Swedish time, (PHP is evil)
	* Getting the formatting was difficult, and I can't be bottered to uppercase the month
	*/
	private function getTimeSwedishFormat(){
		setlocale(LC_TIME,"Swedish");
		$swedishTime = ucfirst(utf8_encode(strftime("%A, den %#d %B &#229;r %Y. Klockan &#228;r [%H:%M:%S]")));

		return $swedishTime;
	}
	/*
	* All that should be in the head of the html document we serve
	*/
	function getHead(){
		$headHtml = "<title>Whatnow</title>";
		return $headHtml;
	}

	function getBody(){
		if($this->model->isUserLoggedIn()){
			$html = "<p>Inloggad</p>" . $this->getMessage() . $this->getLogoutForm();
		} else {
			$html = "<p>Ej inloggad</p>" . $this->getMessage() . $this->getLoginForm();
		}

		$html .= $this->getTimeSwedishFormat();

		return $html;
	}
}