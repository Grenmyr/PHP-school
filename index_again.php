<?php
require_once("controller/LoginController.php");

session_start();
$c = new LoginController();
echo $c->getHTML();