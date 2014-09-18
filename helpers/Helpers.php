<?php
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
