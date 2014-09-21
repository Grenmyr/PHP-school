<?php
require_once("controller/LoginController.php");

date_default_timezone_set("Europe/Stockholm");
setlocale(LC_TIME, "sv_utf8", "Swedish","sv_SE.UTF-8", "Swedish_Sweden.1252");

session_start();
$c = new LoginController();
echo $c->getHTML();