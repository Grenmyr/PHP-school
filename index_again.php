<?php
require_once("controller/LoginController.php");
if(!isset($_SESSION)){
	session_start();
}
$c = new LoginController();
echo $c->getHTML();