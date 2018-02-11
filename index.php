<?php
if($_POST['check'] == 1) {
	process_form();
} else {
	print_form(array());
}

function process_form() {
	$user_p = $_POST['user'];
	$passwd_p = $_POST['password'];

	include_once('inc/mysql.php');

	$user_p = mysql_real_escape_string($user_p);
	$passwd_p = mysql_real_escape_string($passwd_p);

	$loginsql = ""
		. "SELECT *"
		. " FROM instructors, permissions"
		. " WHERE instructors.instr_id LIKE permissions.instr_id"
		. " AND instructors.instr_id like '$user_p'"
		. " AND instr_pass like MD5('$passwd_p')"
		. " AND permissions.perm_level > 0"
		. " ORDER BY perm_level"
		. " LIMIT 1"
		;

	$result = mysql_query($loginsql);

	if(!$result) {
		print_form(array("Database error: " . mysql_error()));
		exit;
	}

	if(mysql_num_rows($result) != 1) {
		print_form(array("Bad username or password."));
		exit;
	} else {
		$row = mysql_fetch_assoc($result);
		$user_db = $row['instr_id'];
		$expire = strtotime(date("F d, Y ") . "23:59:59") + 1;
		setcookie("instr_id", $user_db, $expire);
		setcookie("oo", $oo, $expire);

		print <<<ENDLOGIN
<html>
<head>
<META http-equiv="refresh" content="0;URL=main.php">
<title>Redirecting</title>
</head>
<body>

<h1>Success!</h1>

<p>Your username and password have been accepted and you are being redirected to the main page of this application</p>

<p>You shouldn't even see this page for very long, but if you do, <a href="main.php">click here</a> to continue.</p>

</body>
</html>

ENDLOGIN;
	}
}

function print_form($errors) {
	include('inc/head.php');

	$thisdoc = $_SERVER['PHP_SELF'];

	if(count($errors) >= 1) {
		$errortxt = ""
			. "\n\t<tr><td align='center' colspan='2'>"
			. "\n\n\t<p>\n\t\t<ul>\n\t\t\t<li>\n\t\t\t\t"
			. join("\n\t\t\t</li>\n\t\t\t<li>\n\t\t\t\t", $errors)
			. "\n\t\t\t</li>\n\t\t</ul>\n\t</p>"
			. "\n\t</td></tr>"
			;
	} else { $errortxt = ""; }

	print <<<END
<div id='head'>
	<table align='center'>
	<tr><td align='center'>
	<img src='img/seal.gif'/>
	</td><td align='left'>
	<p><strong>Georgia Southwestern State University</strong></p>
	<p><strong>Teaching Evaluation Page</strong></p>
	<p><strong>
END;

include('inc/showsemester.php');

print <<<END1
</strong></p>
	</td></tr>$errortxt
	</table>
</div>

<div id='content'>
	<table id='login'>
	<form method='post' action='$thisdoc'>
	<tr>
	<td align='right'>
	User ID:
	</td><td align='left'>
	<input size='10' name='user'/>
	</td>
	</tr>
	<tr>
	<td align='right'>
	Password:
	</td><td align='left'>
	<input type='password' size='10' name='password'/>
	</td>
	</tr>
	<tr>
	<td colspan='2' align='center'>
	Your login information is sent over an encrypted connection<br/>
	<br/>
	Your User ID is your GSW ID#, and your Password is "gsweval" (unless you've changed it).<br/>
	<br/>
	If you have problems with this system, please contact Alla Yemelyanov at <a href="mailto:alla.yemelyanov@gsw.edu">alla.yemelyanov@gsw.edu</a> or 229-931-2074.<br/>
	<br/>
	<input type='hidden' name='check' value='1'/>
	<input type='submit' value='Submit'/>
	</td>
	</tr>
	</form>
	</table>
</div>

END1;

	include('inc/foot.php');
}

?>
