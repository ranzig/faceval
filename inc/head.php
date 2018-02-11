<?php
// force SSL connection
if($_SERVER["HTTPS"] != "on") {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
	exit();
}

print <<<ENDHEAD
<html>
<head>
<link rel="stylesheet" type="text/css" href="style/main.css"/>
<title>GSW Teaching Evaluations</title>
</head>
<body>
<div id="wrapper">


ENDHEAD;
?>
