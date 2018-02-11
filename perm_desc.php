<?php
include('inc/getperms.php');
include('inc/functions.php');

if($highest_perm < 3) {
	print_error(array("You don't have permission to be here"));
	die;
}

/* get perms info */
$perms_info = array();

$perm_sql = ""
	. "SELECT *"
	. " FROM permission_desc"
	. " ORDER BY perm_level DESC"
	;

$perm_res = mysql_query($perm_sql);

if(!$perm_res) {
	die("Database error (permissions info): " . mysql_error());
}

print <<<START

<html>
<head>
<link rel="stylesheet" type="text/css" href="style/main.css"/>
<title>GSW Faculty Evaluations</title>
</head>

<body>
<div id='content'>

START;

while($row = mysql_fetch_assoc($perm_res)) {
	$plvl = $row['perm_level'];
	$pname = $row['perm_name'];
	$pdesc = $row['perm_desc'];

	$pdesc = nl2br($pdesc);

	print <<<DESC

<h2>$pname</h2>
<h3>permission level $plvl</h3>

<p>
$pdesc
</p>

DESC;
}

print <<<END

</div>
</body>
</html>
END;
?>