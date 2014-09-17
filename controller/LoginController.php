<?php
require_once("model/UserModel.php");
require_once("view/LoginView.php");
require_once("Helpers/Helpers.php");
require_once("CookieStorage/CookieStorage.php");

class LoginController{
	private $loginView;
	private $model;
	private $message;
	private $username;
	private $password;
	private $messages;

	private static $expiryTime = 108000;
	private static $usernameCookieName = "somethingusername";
	private static $tokenCookieName = "token";

	function __construct(){
		$this->model = new UserModel();
		$this->messages = new CookieStorage();
		$this->loginView = new LoginView($this->model, $this->messages);
	}
	function getHTML(){
		$this->reloadIfDidLogIn();
		return Helpers::getBaseHTML($this->loginView->getHead(), 
									$this->loginView->getBody());
	}
	private function reloadPage(){
		header("location: " . $_SERVER["PHP_SELF"]);
		die;
	}
	private function reloadIfDidLogIn(){
		if($this->didUserLogin()){
			$this->reloadPage();
		}
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
	private function getLoginForm(){
		$loginFormHtml = "<form method='post'>
						Username
						<input type='text' name='username' value={$this->username}>
						Password
						<input type='password' name='password'>
						<input name='rememberme' type='checkbox'>
						<input type='submit' value='Submit'>
					</form>";

			return $loginFormHtml;
	}
	private function getTimeSwedishFormat(){
		setlocale(LC_TIME,"Swedish");
		$swedishTime = ucfirst(utf8_encode(strftime("%A, den %#d %B &#229;r %Y. Klockan &#228;r [%H:%M:%S]")));

		return $swedishTime;
	}
	private function canLoginWithCookie($username, $cookie){
		return $this->model->checkToken($username, $cookie);
	}
	private function loginWithCookie($username, $cookie){
		//If the token in the cookie is correct
		if($this->model->checkToken($username, $cookie)){
			$this->model->loginUser($username);
			//Save a new cookie
			$this->saveRememberMeCookie($username);
			return;
		} else {
			//Invalid cookie
			$this->messages->save("Felaktig information i cookie");
			$this->reloadPage();
		}
		return;
	}
	function removeRememberMeCookies($username){
		setcookie(self::$usernameCookieName, false, -1);
		setcookie(self::$tokenCookieName, false, -1);
		return;
	}
	private function saveRememberMeCookie($username){
		$token = $this->model->getNewToken();

		//Save the token for the user and also set the expiry time so that you may not cheat
		$this->model->saveToken($username, $token, time()+self::$expiryTime);

		setcookie(self::$usernameCookieName, $username, time()+self::$expiryTime);
		setcookie(self::$tokenCookieName, $token, time()+self::$expiryTime);

		return;
	}
	function didUserLogin(){
		if(!empty($_COOKIE[self::$usernameCookieName]) && 
			//and token must be set
			!empty($_COOKIE[self::$tokenCookieName]) && 
			//and we must not be logged in already, otherwise -> redirect loop
			!$this->model->isUserLoggedIn()){
			//this is really ugly
			if($this->canLoginWithCookie($_COOKIE[self::$usernameCookieName], $_COOKIE[self::$tokenCookieName])){
				$this->loginWithCookie($_COOKIE[self::$usernameCookieName], $_COOKIE[self::$tokenCookieName]);
				$this->messages->save("Inloggning lyckades via cookies");
				return true;
			}
		}
		if($_SERVER["REQUEST_METHOD"]!=="POST"){
			return false;
		}
		if(isset($_POST["logout"])){
			$this->model->logoutUser();
			$this->messages->save("Du har nu loggat ut");
			$this->removeRememberMeCookies($_COOKIE["username"]);
			$this->reloadPage();
		}
		//somethingusername must be set
		
		//If username is missing from the post we may not login
		if(empty($_POST["username"])){
			$this->messages->save("Användarnamn saknas");
			return false;
		}
		//Otherwise we got something in here
		$postUsername = $_POST["username"];

		//If password is missing from the post we may absolutely not login
		if(empty($_POST["password"])){
			//but save the postUsername so we can use it in the view
			$this->username = $postUsername;
			$this->messages->save("Lösenord saknas");
			return false;
		}
		//Otherwise we got something in here aswell
		$postPassword = $_POST["password"];

		//This is also really ugly with 2 nested ifs
		if($this->model->userExists($postUsername,$postPassword)){
			$this->model->loginUser($postUsername);		
			if(!empty($_POST["rememberme"])){
				$this->saveRememberMeCookie($postUsername);
				$this->messages->save("Inloggning lyckades och vi kommer ihåg dig nästa gång");
				return true;
			} else {
				$this->messages->save("Inloggnig lyckades");
				return true;	
			}
		}
		$this->messages->save("Felaktigt användarnamn och/eller lösenord");
		return false;
	}
}