<?php
if($_POST['check'] == 1) {
	process_form();
} else {
	if($_GET['um'] == '1') {
		process_unmask();
	} else {
		print_form();
	}
}

function process_unmask() {
	include('inc/getperms.php');
	include('inc/functions.php');

	if($_COOKIE['masq'] == '') {
		print_error(array("You're not masqerading."));
		die;
	}

	$newid = $_COOKIE['masq'];

	$expire = strtotime(date("F d, Y ") . "23:59:59") + 1;

	setcookie('instr_id', $newid, $expire);
	setcookie('masq', '');

	include('inc/insidehead.php');

	print <<<UNMASQ

<div id='content'>

</h1>You've been unmasqed!</h1>

<p>
You now have the same priviledges you did before you started masquerading.
</p>

<p>
<a href='main.php'>Click here</a> to go to the main page, or <a href='masq.php'>click here</a> to masquerade as someone else.
</p>

</div>

UNMASQ;

	include('inc/insidefoot.php');
}

function process_form() {
	include('inc/getperms.php');
	include('inc/functions.php');

	if($highest_perm < 3) {
		print_error(array("You don't have permission to be here"));
		die;
	}

	if($_COOKIE['masq'] != '') {
		$error = "You are already masquerading.  Please unmask before impersonating someone else.";
		print_error(array($error));
		$die;
	}

	$oldid = $_COOKIE['instr_id'];
	$newid = $_POST['instructor'];

	$expire = strtotime(date("F d, Y ") . "23:59:59") + 1;

	setcookie('masq', $oldid, $expire);
	setcookie('instr_id', $newid, $expire);

	include('inc/insidehead.php');

	print <<<MASQ

<div id='content'>

<h1>Success!</h1>

<p>
On your next page load, you will have the same permissions as instructor $newid, and the "logout" link (at the top of the page) will change to "unmask".  To stop masquerading, click the "unmasq" link.
</p>

<p>
<a href='main.php'>Click here</a> to go to the main GSW Evaluations page.
</p>

</div>

MASQ;

	include('inc/insidefoot.php');
}

function print_form() {
	include('inc/getperms.php');
	include('inc/functions.php');

	if($highest_perm < 3) {
		print_error(array("You don't have permission to be here"));
		die;
	}

	/* get instructor info */
	include_once('inc/mysql.php');

	$instr_sql = ""
		. "SELECT DISTINCT instructors.instr_id,"
		. " instr_lname, instr_fname"
		. " FROM instructors, permissions"
		. " WHERE instructors.instr_id = permissions.instr_id"
		. " AND perm_level <= 2"
		. " ORDER BY instr_lname"
		;

	$instr_res = mysql_query($instr_sql);

	if(!$instr_res) {
		die("Database error (get instructor info): " . mysql_error());
	}

	$instr_opt = "";
	while($row = mysql_fetch_assoc($instr_res)) {
		$iid = $row['instr_id'];
		$lname = $row['instr_lname'];
		$fname = $row['instr_fname'];
		if($fname == '') {
			$name = $lname;
		} else {
			$name = "$lname, $fname";
		}

		$instr_opt .= "\n\t<option value='$iid'>$name ($iid)</option>";
	}

	$thisdoc = $_SERVER['PHP_SELF'];

	include('inc/insidehead.php');

	print <<<ENDFORM

<div id='content'>

<h1>Instructor masquerading</h1>

<p>
Select the instructor you wish to impersonate below and click "Masquerade".
</p>

<p>
<form method='post' action='$thisdoc'>
<input type='hidden' name='check' value='1'/>
<select name='instructor'>$instr_opt
</select>
<input type='submit' value='Masquerade'/>
</form>
</p>

</div>

ENDFORM;

	include('inc/insidefoot.php');
}
?>