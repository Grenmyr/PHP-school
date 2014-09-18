<?php
require_once("controller/LoginController.php");

session_start();
if(isset($_SESSION["HTTP_USER_AGENT"])){
	if($_SESSION["HTTP_USER_AGENT"] != md5($_SERVER["HTTP_USER_AGENT"])){
		$c = new LoginController();
		echo $c->getLogin();
		exit;
	}
}
else {
	$_SESSION["HTTP_USER_AGENT"] = md5($_SERVER["HTTP_USER_AGENT"]);
}

$c = new LoginController();
echo $c->getHTML();