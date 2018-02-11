<?php
$newoo = $_POST['oo'];
if($newoo == 1 || $newoo == 2) {
	setcookie("oo", $newoo);
} else {
	setcookie("oo", 0);
}

$link = $_SERVER['HTTP_REFERER'];

if($link == '') {
	$link = 'main.php';
}

print <<<ENDHTML
<html>
<head>
<title>Redirecting...</title>
<META http-equiv="refresh" content="0;URL=$link">
</head>
<body>
<h1>Redirecting...</h1>
<p>Your filter choice has been successfully applied.  If you're not redirected in the next few seconds, click <a href='$link'>here</a>
</body>
</html>
ENDHTML;
?>