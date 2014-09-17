<?php
if(!isset($_SESSION)){
	session_start();
}
class Helpers{
	static function getBaseHTML($head = "", $body = ""){
		$html = "<!DOCTYPE html>
		<html lang='en'>
		<head>
			<meta charset='UTF-8'>
			$head
		</head>
		<body>
			$body
		</body>
		</html>";
		return $html;
	}
}
class LoginView{
	private $model;
	function __construct(UserModel $model){
		$this->model = $model;
	}
	function didUserLogin(){
		if(isset($_POST["username"]) && isset($_POST["password"])){
			$_SESSION["loggedin"] = true;
			return true;
		}
		return false;
	}
	function isUserLoggedIn(){
		if(isset($_SESSION["loggedin"])){
			return true;
		}
		return false;
	}
	function getHead(){
		return "<title>Whatnow</title>";
	}
	function getBody(){
		if($this->didUserLogin()){
			header("location:".$_SERVER["PHP_SELF"]);
		}
		if($this->isUserLoggedIn()){
			$html = "Inloggad<br>";
		}
		else {
		$html .= <<<'EOT'
			<form method="post">
					Username
					<input type="text" name="username">
					Password
					<input type="password" name="password">
					<input type="submit" value="Submit">
			</form>
EOT;
}
setlocale(LC_TIME,"Swedish");
$html.=ucfirst(utf8_encode(strftime("%A, den %#d %B"). " år " . strftime("%Y. Klockan är [%H:%M:%S]")));
		return $html;
	}
}
class LoginController{
	private $loginView;
	function __construct(){
		$this->loginView = new LoginView(new UserModel("Hej", "Nej"));
	}
	function getHTML(){
		
		return Helpers::getBaseHTML($this->loginView->getHead(), $this->loginView->getBody());
	}
}
class UserModel{
	private $username;
	private $password;

	function __construct($username, $password){
		$this->username = $username;
		$this->password = $password;
	}
	function userExists($username, $password){
		return false;
	}
}
var_dump($_POST);
$c = new LoginController();
echo $c->getHTML();