<?php
if(!isset($_SESSION)){
	session_start();
}
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
class Helpers{	
	// Returns the base of all HTML documents that we should use
	static function getBaseHTML($head = "", $body = ""){
		$html = "<!DOCTYPE html>
			<html lang='en'>
			<head>
				<meta charset='UTF-8'>
				{$head}
			</head>
			<body>
				{$body}
			</body>
			</html>";
		return $html;
	}
}
class LoginView{
	private $model;
	private $message;
	private $username;
	private $password;
	private $messages;

	private static $expiryTime = 60*60*30;

	function __construct(UserModel $model){
		$this->model = $model;
		$this->messages = new CookieStorage();
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
		setcookie("somethingusername", false, -1);
		setcookie("token", false, -1);
		return;
	}
	private function saveRememberMeCookie($username){
		$token = $this->model->getNewToken();

		//Save the token for the user and also set the expiry time so that you may not cheat
		$this->model->saveToken($username, $token, time()+self::$expiryTime);

		setcookie("somethingusername", $username, time()+self::$expiryTime);
		setcookie("token", $token, time()+self::$expiryTime);

		return;
	}
	function didUserLogin(){
		if($_SERVER["REQUEST_METHOD"]!=="POST"){
			return false;
		}
		if(!empty($_POST["logout"])){
			$this->model->logoutUser();
			$this->messages->save("Du har nu loggat ut");
			$this->removeRememberMeCookies($_COOKIE["username"]);
			$this->reloadPage();
		}
		//somethingusername must be set
		if(!empty($_COOKIE["somethingusername"]) && 
			//and token must be set
			!empty($_COOKIE["token"]) && 
			//and we must not be logged in already, otherwise -> redirect loop
			!$this->model->isUserLoggedIn()){
			//this is really ugly
			if($this->canLoginWithCookie($_COOKIE["somethingusername"], $_COOKIE["token"])){
				$this->loginWithCookie($_COOKIE["somethingusername"], $_COOKIE["token"]);
				$this->messages->save("Inloggning lyckades via cookies");
				return true;
			}
		}
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
	/*
	* All that should be in the head of the html document we serve
	*/
	function getHead(){
		$headHtml = "<title>Whatnow</title>";
		return $headHtml;
	}
	function getBody(){
		$this->reloadIfDidLogIn();

		if($this->model->isUserLoggedIn()){
			$html = "<p>Inloggad</p>" . $this->getMessage() . $this->getLogoutForm();
		} else {
			$html = "<p>Ej inloggad</p>" . $this->getMessage() . $this->getLoginForm();
		}

		$html .= $this->getTimeSwedishFormat();

		return $html;
	}
}
class LoginController{
	private $loginView;

	function __construct(){
		$this->loginView = new LoginView(new UserModel());
	}
	function getHTML(){
		return Helpers::getBaseHTML($this->loginView->getHead(), 
									$this->loginView->getBody());
	}
}
// Represents a User and allows us to check if the user exists
class UserModel{
	private $username;
	private $password;

	function __construct($username = "", $password = ""){
		$this->username = $username;
		$this->password = $password;
	}
	// Checks if the user exists
	function userExists($username, $password){
		if($username == 'Admin' && $password == 'Password'){
			return true;
		}
		return false;
	}
	function loginUser($username){
		$_SESSION["loggedin"] = true;
		$_SESSION["remote_addr"] = $_SERVER["REMOTE_ADDR"];
		$_SESSION["useragent"] = $_SERVER["HTTP_USER_AGENT"];
		return;
	}
	function logoutUser(){
		unset($_SESSION["loggedin"]);
		unset($_SESSION["remote_addr"]);
		unset($_SESSION["useragent"]);
		return;
	}
	function isUserLoggedIn(){
		if(isset($_SESSION["loggedin"])){
			return true;
		}
		return false;
	}
	function checkToken($username, $cookie){
		print_r($username);
		print_r($cookie);
		list($token, $expirytime) = $this->getTokenFromFile($username);
		print_r($token);
		if($token === $cookie && $expirytime >= time()){
			return true;
		}
		return false;
	}
	function getTokenFromFile($username){
		$fp = fopen($username, 'r');
		$content = fread($fp,filesize($username));
		fclose($fp);
		list($token, $expirytime) = explode("\n",$content);
		return array($token, $expirytime);
	}
	function saveToken($username, $token, $expirytime){
		$fp = fopen($username, 'w');
		fwrite($fp, $token . "\n" . $expirytime);
		fclose($fp);
	}
	function getNewToken(){
		return "hejdär";
	}
}
print_r($_COOKIE);
$c = new LoginController();
echo $c->getHTML();