<?php
include('inc/getperms.php');
include('inc/functions.php');

if($_POST['check'] == 1) {
	process_form($highest_perm, $_GET['s']);
} else if(($highest_perm < 3) || ($_GET['s'] == 1)) {
	print_form(array(), array());
} else {
	print_admin(array(), array());
}

function process_form($highperm, $self) {
	$errors = array();
	$info = array();

	$iid = $_POST['instr_id'];	

	if($highperm < 3 || $self == 1) {
		/* check old password first */
		$oldpw = md5($_POST['oldpass']);

		if($_POST['oldpass'] == '') {
			$error = "You must input your old password";
			array_push($errors, $error);
			print_form($errors, array());
			die;
		}

		include_once('inc/mysql.php');

		$cp_sql = ""
			. "SELECT *"
			. " FROM instructors"
			. " WHERE instr_id LIKE '$iid'"
			. " AND instr_pass LIKE '$oldpw'"
			;

		$cp_res = mysql_query($cp_sql);

		if(!$cp_res) {
			die("Database error: " . mysql_error());
		}

		if(mysql_num_rows($cp_res) < 1) {
			array_push($errors, "Your old password is not correct");
		}
	}

	$newpass = md5($_POST['pass1']);
	$confirm = md5($_POST['pass2']);

	if($newpass != $confirm) {
		$error = "Your new password and confirmation don't match";
		array_push($errors, $error);
	}

	if(($_POST['pass1'] == '') || ($_POST['pass2'] == '')) {
		array_push($errors, "Your new password cannot be blank");
	}

	if(count($errors) > 0) {
		if($highperm < 3 || $self == 1) {
			print_form($errors, $info);
		} else {
			print_admin($errors, $info);
		}

		die;
	}

	$pu_sql = ""
		. "UPDATE instructors"
		. " SET instr_pass = '$newpass'"
		. " WHERE instr_id LIKE '$iid'"
		;

	$pu_res = mysql_query($pu_sql);

	if(!$pu_res) {
		die("Database error (update password): " . mysql_error());
	}

	if($highperm < 3 || $self == 1) {
		array_push($info, "Your password has been updated!");
		print_form($errors, $info);
	} else {
		array_push($info, "Password for instructor $iid has been updated!");
		print_admin($errors, $info);
	}

	
}

function print_form($errors, $info) {
	$iid = $_COOKIE['instr_id'];
	$thisdoc = $_SERVER['PHP_SELF'];

	if($_GET['s'] == 1) {
		$thisdoc .= '?s=1';
	}

	include('inc/insidehead.php');

	print "<div id='content'>\n";

	if(count($errors) > 0) {
		print "<h4>Errors!</h4>\n<ul>";

		foreach($errors as $error) {
			print "<li>$error</li>\n";
		}

		print "</ul>\n";
	}

	if(count($info) > 0) {
		print "<h2>Information</h2>\n<ul>\n";

		foreach($info as $line) {
			print "<li>$line</li>\n";
		}

		print "</ul>\n";
	}

	print <<<REGFORM

<h1>Change Password</h1>

<form method='post' action='$thisdoc'>
<input type='hidden' name='check' value='1'/>
<input type='hidden' name='instr_id' value='$iid'/>
<table>
<tr>
<td class='rightc'>
Current password:
</td>
<td>
<input type='password' name='oldpass'/>
</td>
</tr>
<tr>
<td class='rightc'>
New password:
</td>
<td>
<input type='password' name='pass1'/>
</td>
</tr>
<tr>
<td class='rightc'>
Confirm new password:
</td>
<td>
<input type='password' name='pass2'/>
</td>
</tr>
<tr>
<td class='cenc' colspan='2'>
<input type='submit' value='Change Password'/>
</td>
</tr>
</table>
</form>

</div>

REGFORM;

	include('inc/insidefoot.php');
}

function print_admin($errors, $info) {
	/* first get list of instructors */
	include_once('inc/mysql.php');

	$instr_sql = ""
		. "SELECT instr_id, instr_lname, instr_fname"
		. " FROM instructors"
		. " ORDER BY instr_lname"
		;

	$instr_res = mysql_query($instr_sql);

	if(!$instr_res) {
		die("Database error (get instructors): " . mysql_error());
	}

	$isel_txt = ""
		. "<select name='instr_id'>\n"
		. "\t<option disabled='true' selected='true'>"
		. "Instructor"
		. "</option>\n"
		;

	while($row = mysql_fetch_assoc($instr_res)) {
		$iid = $row['instr_id'];
		$name = $row['instr_lname'];

		if($row['instr_fname'] != '') {
			$name .= ", " . $row['instr_fname'];
		}

		$isel_txt .= "\t<option value='$iid'>$name ($iid)</option>\n";
	}

	$isel_txt .= "</select>\n";

	/* now print the form */
	$thisdoc = $_SERVER['PHP_SELF'];

	if($_GET['s'] == 1) {
		$thisdoc .= '?s=1';
	}

	include('inc/insidehead.php');

	print "<div id='content'>\n";

	if(count($errors) > 0) {
		print "<h4>Errors!</h4>\n<ul>";

		foreach($errors as $error) {
			print "<li>$error</li>\n";
		}

		print "</ul>\n";
	}

	if(count($info) > 0) {
		print "<h2>Information</h2>\n<ul>\n";

		foreach($info as $line) {
			print "<li>$line</li>\n";
		}

		print "</ul>\n";
	}

	print <<<ADMINFORM

<h1>Reset instructor password</h1>

<p>
Select the instructor's name from below, enter the desired password (and confirm), and click "Reset Password".
</p>

<form method='post' action='$thisdoc'>
<input type='hidden' name='check' value='1'/>
<table>
<tr>
<td class='rightc'>
Instructor:
</td>
<td>
$isel_txt
</td>
</tr>
<tr>
<td class='rightc'>
New password:
</td>
<td>
<input type='password' name='pass1'/>
</td>
</tr>
<tr>
<td class='rightc'>
Confirm new password:
</td>
<td>
<input type='password' name='pass2'/>
</td>
</tr>
<tr>
<td class='cenc' colspan='2'>
<input type='submit' value='Reset Password'/>
</td>
</tr>
</table>
</form>

ADMINFORM;

	include('inc/insidefoot.php');
}
?>