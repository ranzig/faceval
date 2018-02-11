<?php
// force ssl
if($_SERVER["HTTPS"] != "on") {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: https://"
		. $_SERVER["SERVER_NAME"]
		. $_SERVER["REQUEST_URI"]
	);
	exit();
}

// check if this person is logged in and get permissions
$permissions = array();
$adminlnktxt = "";
$highest_perm = 0;

if($_COOKIE['instr_id'] != '') {
	$instr_id = $_COOKIE['instr_id'];

	include_once('inc/mysql.php');

	$permsql = ""
		. "SELECT *"
		. " FROM permissions"
		. " WHERE instr_id like '$instr_id'"
		. " ORDER BY perm_level DESC";
		;

	$perm_result = mysql_query($permsql);

	if(!$perm_result) {
		die("Database Error (permissions)");
	}

	while($record = mysql_fetch_assoc($perm_result)) {
		$perm_dept_id = $record['dept_id'];
		$perm_dept_lvl = $record['perm_level'];

		$permhash = 'dept' . $perm_dept_id;

		if($permissions[$permhash] < $perm_dept_lvl) {
			$permissions[$permhash] = $perm_dept_lvl;
		}

		if($record['perm_level'] > $highest_perm) {
			$highest_perm = $record['perm_level'];
		}
	}

	if($highest_perm >= 3) {
		$adminlnktxt = "\n\t<a href='admin.php'>admin page</a><br/>";
	}
} else {
	header("Location:./");
}

if($_COOKIE['masq'] == '') {
	$logoutlink = "<a href='logout.php'>logout</a><br/>";
} else {
	$logoutlink = "<a href='masq.php?um=1'>unmasq</a><br/>";
}

print <<<ENDINHEAD1
<html>
<head>
<link rel="stylesheet" type="text/css" href="style/main.css"/>
<title>GSW Teaching Evaluations</title>
</head>
<body>
<div id="wrapper">

<div id='head'>
	<table width='800px'>
	<tr><td width='100px' align='center'>
	<img src='img/seal.gif'/>
	</td><td width='400px' align='left'>
	<p><strong>Georgia Southwestern State University</strong></p>
	<p><strong>Teaching Evaluation Page</strong></p>
	<p><strong>
ENDINHEAD1;

$sid = $_GET['sid'];

include_once('mysql.php');

if($sid != '') {
	$semestersql = ""
		. "SELECT * "
		. " FROM semesters"
		. " WHERE semester_id = $sid"
		. " AND active = 1"
		. " LIMIT 1"
		;
} else {
	$semestersql = ""
		. "SELECT * "
		. " FROM semesters"
		. " WHERE active = 1"
		. " ORDER BY semester_id DESC"
		. " LIMIT 1"
		;
}

$result = mysql_query($semestersql);

if(!$result) {
	die("Database error (semester): " . mysql_error());
}

$semester = mysql_fetch_assoc($result);
$sem_name = $semester['semester_name'];
$sem_year = $semester['semester_year'];

print "$sem_name $sem_year";

print <<<ENDINHEAD3
</strong></p>

<!-- MOTD -->
<p>

ENDINHEAD3;

include('motd.html');

print <<<ENDINHEAD4
</p>

	</td><td width='300px' align='right'>
	<p>$adminlnktxt
	<a href='password.php?s=1'>change password</a>
	</p>
	<a href='instructor.php?sid=$sid'>my course evaluation</a><br/>
	<a href='main.php?sid=$sid'>back to main</a><br/>
	<a href='#' onclick='history.go(-1);return false;'>back</a><br/>
	$logoutlink
	</p>
	<p>
ENDINHEAD4;

if($_COOKIE['oo'] == 1) {
	$oosel0 = "";
	$oosel1 = " selected='true'";
	$oosel2 = "";
} else if($_COOKIE['oo'] == 2) {
	$oosel0 = "";
	$oosel1 = "";
	$oosel2 = " selected='true'";
} else {
	$oosel0 = " selected='true'";
	$oosel1 = "";
	$oosel2 = "";
}

print <<<ENDINHEAD5

	<form method='post' name='oochange' action='oochange.php'>

ENDINHEAD5;

foreach($_GET as $getkey => $getval) {
	if($getkey != 'sid') {
		print "\t\t<input type='hidden' name='$getkey' value='$getval'>\n";
	}
}

print <<<ENDINHEAD6
		<select name='oo' onChange="document.oochange.submit()">
		<option value='0'$oosel0>All classes</option>
		<option value='1'$oosel1>Online classes</option>
		<option value='2'$oosel2>Regular classes</option>
		</select>
	</form>

ENDINHEAD6;

print <<<ENDINHEAD4

	</p>
	<p>
	<form method='get' name='termchange'>

ENDINHEAD4;

foreach($_GET as $getkey => $getval) {
	if($getkey != 'sid') {
		print "\t\t<input type='hidden' name='$getkey' value='$getval'>\n";
	}
}

print <<<ENDINHEAD7
		<select name='sid' onChange="document.termchange.submit()">
		<option disabled='true' selected='true'>Select Term</option>

ENDINHEAD7;

include('inc/semestermenu.php');

print <<<ENDINHEAD2
		</select>
	</form>
	</p>
	</td></tr>
	</table>

	<hr/>
</div>

ENDINHEAD2;
?>
